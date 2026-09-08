import { ApiError, get, getSession, post, remove, setCsrfToken } from './api.js';

const cardId = Number(document.body.dataset.cardId || 0);
const $ = (selector) => document.querySelector(selector);
let currentCard = null;
let imageObjectUrl = '';
const deleteDialog = $('#detail-delete-dialog');
let deleteTrigger = null;

const rarityLabels = Object.freeze({
    Common: 'Comum',
    Uncommon: 'Incomum',
    Rare: 'Rara',
    'Mythic Rare': 'Rara mítica',
    Special: 'Especial',
    Bonus: 'Bônus',
    'Double Rare': 'Dupla rara',
    'Ultra Rare': 'Ultra rara',
    'Illustration Rare': 'Rara de ilustração',
    'Special Illustration Rare': 'Rara de ilustração especial',
    'Hyper Rare': 'Rara hiper',
    'ACE SPEC Rare': 'Rara ACE SPEC',
    Promo: 'Promocional',
    'Super Rare': 'Super rara',
    'Secret Rare': 'Rara secreta',
    'Ultimate Rare': 'Rara Ultimate',
    'Ghost Rare': 'Rara Ghost',
    "Collector's Rare": 'Rara de colecionador',
    'Starlight Rare': 'Rara Starlight',
    'Quarter Century Secret Rare': 'Rara secreta de quarto de século',
});

const gameLabels = Object.freeze({
    magic: 'Magic: The Gathering',
    pokemon: 'Pokémon',
    yugioh: 'Yu-Gi-Oh!',
});

function rarityLabel(value) {
    return rarityLabels[value] || value || '—';
}

function setText(selector, value) {
    const node = $(selector);
    if (node) node.textContent = value || '—';
}

function renderCard(card) {
    const game = gameLabels[card.game] || card.game_label || 'Jogo';
    const rarity = rarityLabel(card.rarity);
    const image = $('#detail-image');
    const thumb = $('#detail-front-thumb img');

    if (image) {
        image.src = card.image_url;
        image.alt = `Imagem de ${card.name_en}`;
        image.onerror = () => { image.src = '/assets/images/card-fallback.svg'; };
    }
    if (thumb) {
        thumb.src = card.image_url;
        thumb.alt = '';
        thumb.onerror = () => { thumb.src = '/assets/images/card-fallback.svg'; };
    }
    setText('#detail-game', game);
    setText('#detail-card-name', card.name_en);
    setText('#detail-card-pt', card.name_pt || 'Sem tradução cadastrada');
    setText('#detail-rarity', rarity);
    setText('#detail-edition', card.edition_label);
    setText('#meta-game', game);
    setText('#meta-edition', card.edition_label);
    setText('#meta-rarity', rarity);

    currentCard = card;
    document.querySelector('#main-content')?.setAttribute('aria-busy', 'false');
}

function showMessage(message) {
    const node = $('#detail-message');
    if (node) node.textContent = message;
}

function detailFormField(name) {
    return $(`#detail-field-${name}`);
}

function setDetailOptions(select, items, placeholder, selected = '') {
    select.replaceChildren(new Option(placeholder, ''));
    items.forEach((item) => {
        const value = item.id || item;
        const label = item.name || item;
        const option = new Option(label, value);
        option.selected = String(value) === String(selected);
        select.append(option);
    });
}

async function loadDetailOptions(game, edition = '', rarity = '') {
    const editionSelect = detailFormField('edition');
    const raritySelect = detailFormField('rarity');
    if (!game) {
        setDetailOptions(editionSelect, [], 'Escolha o jogo primeiro');
        setDetailOptions(raritySelect, [], 'Escolha o jogo primeiro');
        editionSelect.disabled = true;
        raritySelect.disabled = true;
        return;
    }
    setDetailOptions(editionSelect, [], 'Carregando coleções...');
    setDetailOptions(raritySelect, [], 'Carregando raridades...');
    editionSelect.disabled = true;
    raritySelect.disabled = true;
    try {
        const response = await get(`/api/catalog/options?game=${encodeURIComponent(game)}`);
        setDetailOptions(editionSelect, response.data?.editions || [], 'Escolha uma coleção', edition);
        setDetailOptions(raritySelect, response.data?.rarities || [], 'Escolha uma raridade', rarity);
        editionSelect.disabled = false;
        raritySelect.disabled = false;
    } catch (error) {
        setDetailOptions(editionSelect, [], 'Não foi possível carregar');
        setDetailOptions(raritySelect, [], 'Não foi possível carregar');
        showMessage(error instanceof ApiError ? error.message : 'Não foi possível carregar as opções.');
    }
}

function updateDetailImageSourcePanels() {
    const source = $('input[name="image_source"]:checked', $('#detail-card-form'))?.value || 'upload';
    $('#detail-upload-panel').hidden = source !== 'upload';
    $('#detail-url-panel').hidden = source !== 'url';
    document.querySelectorAll('#detail-card-form .source-tab').forEach((tab) => tab.classList.toggle('is-active', tab.querySelector('input').checked));
    const file = detailFormField('image')?.files?.[0];
    const label = $('#detail-upload-zone-label');
    const help = $('#detail-upload-zone-help');
    if (label) label.textContent = file?.name || 'Escolha uma imagem';
    if (help) help.hidden = Boolean(file?.name);
}

function clearDetailEditorErrors() {
    document.querySelectorAll('[data-detail-error-for]').forEach((node) => { node.textContent = ''; });
    $('#detail-card-form')?.querySelector('.image-fieldset')?.classList.remove('has-error');
}

function showDetailEditorErrors(fields) {
    clearDetailEditorErrors();
    Object.entries(fields).forEach(([field, message]) => {
        const node = document.querySelector(`[data-detail-error-for="${field}"]`);
        if (node) node.textContent = message;
    });
}

function validateDetailEditor() {
    const fields = {};
    if (!detailFormField('name-en').value.trim()) fields.name_en = 'Informe o nome em inglês.';
    if (!detailFormField('game').value) fields.game = 'Escolha um jogo.';
    if (!detailFormField('edition').value) fields.edition_id = 'Escolha uma coleção.';
    if (!detailFormField('rarity').value) fields.rarity = 'Escolha uma raridade.';
    const source = $('input[name="image_source"]:checked', $('#detail-card-form'))?.value;
    if (source === 'url' && !detailFormField('image-url').value.trim()) fields.image_value = 'Informe a URL da imagem.';
    if (source === 'upload' && !detailFormField('image').files.length && !currentCard?.image_value) fields.image = 'Escolha uma imagem ou use uma URL.';
    if (Object.keys(fields).length) {
        showDetailEditorErrors(fields);
        return false;
    }
    return true;
}

async function openEditor() {
    if (!currentCard) return;
    const form = $('#detail-card-form');
    detailFormField('name-en').value = currentCard.name_en || '';
    detailFormField('name-pt').value = currentCard.name_pt || '';
    detailFormField('game').value = currentCard.game || '';
    detailFormField('image-url').value = currentCard.image_source === 'url' ? currentCard.image_value || '' : '';
    const source = form.querySelector(`input[name="image_source"][value="${currentCard.image_source || 'upload'}"]`);
    if (source) source.checked = true;
    detailFormField('image').value = '';
    clearDetailEditorErrors();
    await loadDetailOptions(currentCard.game, currentCard.edition_id, currentCard.rarity);
    updateDetailImageSourcePanels();
    $('#detail-card-drawer').classList.add('is-open');
    $('#detail-card-drawer').setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    detailFormField('name-en').focus();
}

function closeEditor() {
    if (imageObjectUrl) {
        URL.revokeObjectURL(imageObjectUrl);
        imageObjectUrl = '';
    }
    const drawer = $('#detail-card-drawer');
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

async function submitEditor(event) {
    event.preventDefault();
    if (!validateDetailEditor()) return;
    const submit = $('#detail-form-submit');
    submit.disabled = true;
    try {
        const form = $('#detail-card-form');
        const data = new FormData(form);
        data.append('_method', 'PUT');
        const source = $('input[name="image_source"]:checked', form)?.value;
        if (source === 'upload' && currentCard?.image_value && !detailFormField('image').files.length) data.append('retain_image', '1');
        const response = await post(`/api/cards/${cardId}`, data);
        closeEditor();
        renderCard(response.data?.item || currentCard);
        showMessage('Carta atualizada.');
    } catch (error) {
        if (error instanceof ApiError) showDetailEditorErrors(error.fields || {});
        showMessage(error instanceof ApiError ? error.message : 'Não foi possível atualizar a carta.');
    } finally {
        submit.disabled = false;
    }
}

function openDeleteDialog() {
    if (!deleteDialog) return;
    deleteTrigger = $('#delete-card-button');
    $('#detail-delete-card-name').textContent = currentCard?.name_en || 'esta carta';
    if (typeof deleteDialog.showModal === 'function') deleteDialog.showModal();
    else deleteDialog.setAttribute('open', '');
    $('#detail-delete-confirm').focus();
}

function closeDeleteDialog() {
    if (!deleteDialog) return;
    if (typeof deleteDialog.close === 'function' && deleteDialog.open) deleteDialog.close();
    else deleteDialog.removeAttribute('open');
    deleteTrigger?.focus();
    deleteTrigger = null;
}

async function deleteCard() {
    const button = $('#delete-card-button');
    const confirmButton = $('#detail-delete-confirm');
    if (!button || !confirmButton) return;
    button.disabled = true;
    confirmButton.disabled = true;
    confirmButton.classList.add('is-processing');
    try {
        await remove(`/api/cards/${cardId}`);
        window.location.replace('/cards');
    } catch (error) {
        showMessage(error instanceof ApiError ? error.message : 'Não foi possível excluir a carta.');
        button.disabled = false;
        confirmButton.disabled = false;
        confirmButton.classList.remove('is-processing');
    }
}

async function init() {
    if (!cardId) {
        showMessage('Carta não encontrada.');
        return;
    }

    $('#edit-card-button')?.addEventListener('click', openEditor);
    $('#delete-card-button')?.addEventListener('click', openDeleteDialog);
    $('#detail-delete-close')?.addEventListener('click', closeDeleteDialog);
    $('#detail-delete-cancel')?.addEventListener('click', closeDeleteDialog);
    $('#detail-delete-confirm')?.addEventListener('click', deleteCard);
    deleteDialog?.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeDeleteDialog();
    });
    deleteDialog?.addEventListener('click', (event) => {
        if (event.target === deleteDialog) closeDeleteDialog();
    });
    $('#detail-drawer-close')?.addEventListener('click', closeEditor);
    $('#detail-drawer-backdrop')?.addEventListener('click', closeEditor);
    $('#detail-form-cancel')?.addEventListener('click', closeEditor);
    $('#detail-card-form')?.addEventListener('submit', submitEditor);
    $('#detail-field-game')?.addEventListener('change', (event) => loadDetailOptions(event.target.value));
    $('#detail-card-form')?.querySelectorAll('input[name="image_source"]').forEach((input) => input.addEventListener('change', updateDetailImageSourcePanels));
    $('#detail-field-image')?.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            showDetailEditorErrors({ image: 'Use JPG, PNG ou WebP de até 5 MB.' });
            event.target.value = '';
            updateDetailImageSourcePanels();
            return;
        }
        if (imageObjectUrl) URL.revokeObjectURL(imageObjectUrl);
        imageObjectUrl = URL.createObjectURL(file);
        clearDetailEditorErrors();
        updateDetailImageSourcePanels();
    });
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && deleteDialog?.open) closeDeleteDialog();
        if (event.key === 'Escape' && $('#detail-card-drawer')?.classList.contains('is-open')) closeEditor();
    });

    try {
        const session = await getSession();
        if (!session.data?.authenticated) {
            window.location.replace('/login');
            return;
        }
        setCsrfToken(session.data.csrf_token);
        const response = await get(`/api/cards/${cardId}`);
        renderCard(response.data?.item || {});
    } catch (error) {
        if (error instanceof ApiError && error.status === 401) {
            window.location.replace('/login');
            return;
        }
        document.querySelector('#main-content')?.setAttribute('aria-busy', 'false');
        showMessage(error instanceof ApiError ? error.message : 'Não foi possível carregar a carta.');
    }
}

$('#logout-button')?.addEventListener('click', async () => {
    try {
        await post('/api/auth/logout', {});
    } finally {
        window.location.replace('/login');
    }
});

window.addEventListener('cardmancer:session-expired', () => window.location.replace('/login?expired=1'), { once: true });
init();
