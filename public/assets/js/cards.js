import { ApiError, get, getSession, post, remove, setCsrfToken } from './api.js';

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

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

const state = {
    q: '',
    game: '',
    page: 1,
    perPage: 4,
    sort: 'updated',
    direction: 'desc',
    items: [],
    total: 0,
    totalPages: 1,
    selectedId: null,
    selectedIds: new Set(),
    drawerMode: 'create',
    currentCard: null,
    imageObjectUrl: '',
    imageCleared: false,
    dirty: false,
    optionsRequest: 0,
    searchTimer: 0,
    deleteId: null,
    editId: null,
    newRequested: false,
};

const tableState = $('#table-state');
const loadingState = $('#loading-state');
const emptyState = $('#empty-state');
const errorState = $('#error-state');
const tableBody = $('#cards-table-body');
const mobileList = $('#mobile-card-list');
const inspectorEmpty = $('#inspector-empty');
const inspectorContent = $('#inspector-content');
const drawer = $('#card-drawer');
const form = $('#card-form');
const deleteDialog = $('#delete-dialog');
let filterCloseTimer = 0;

function text(tag, content, className = '') {
    const node = document.createElement(tag);
    if (className) node.className = className;
    node.textContent = content ?? '';
    return node;
}

function button(content, className, onClick) {
    const node = text('button', content, className);
    node.type = 'button';
    node.addEventListener('click', onClick);
    return node;
}

function setHidden(node, hidden) {
    node.hidden = hidden;
}

function gameClass(game) {
    return ['magic', 'pokemon', 'yugioh'].includes(game) ? game : '';
}

function formatUpdated(value) {
    if (!value) return 'sem registro';
    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(date);
}

function formatCount(value) {
    return new Intl.NumberFormat('pt-BR').format(value);
}

function readUrlState() {
    const params = new URLSearchParams(window.location.search);
    state.q = params.get('q') || '';
    state.game = params.get('game') || '';
    state.page = Math.max(1, Number(params.get('page') || 1));
    state.perPage = Math.min(100, Math.max(1, Number(params.get('per_page') || 4)));
    state.sort = params.get('sort') || 'updated';
    state.direction = (params.get('direction') || 'desc').toLowerCase() === 'asc' ? 'asc' : 'desc';
    state.selectedId = Number(params.get('selected')) || null;
    state.editId = Number(params.get('edit')) || null;
    state.newRequested = params.get('new') === '1';
    $('#search-input').value = state.q;
    $('#game-filter').value = state.game;
    $('#sort-filter').value = `${state.sort}:${state.direction}`;
    updateFilterResetVisibility();
}

function syncUrl() {
    const params = new URLSearchParams();
    if (state.q) params.set('q', state.q);
    if (state.game) params.set('game', state.game);
    if (state.page > 1) params.set('page', String(state.page));
    if (state.perPage !== 4) params.set('per_page', String(state.perPage));
    if (state.sort !== 'updated') params.set('sort', state.sort);
    if (state.direction !== 'desc') params.set('direction', state.direction);
    if (state.selectedId) params.set('selected', String(state.selectedId));
    if (state.editId) params.set('edit', String(state.editId));
    if (state.newRequested) params.set('new', '1');
    const query = params.toString();
    window.history.replaceState({}, '', `${window.location.pathname}${query ? `?${query}` : ''}`);
    updateFilterResetVisibility();
}

function updateFilterResetVisibility() {
    const reset = $('#reset-filters');
    if (!reset) return;
    reset.hidden = !(state.q || state.game || state.sort !== 'updated' || state.direction !== 'desc');
}

function setLoading(loading) {
    setHidden(loadingState, !loading);
    if (loading) {
        setHidden(tableState, true);
        setHidden(emptyState, true);
        setHidden(errorState, true);
    }
}

function showErrorState(message = '') {
    setHidden(loadingState, true);
    setHidden(tableState, true);
    setHidden(emptyState, true);
    setHidden(errorState, false);
    $('#catalog-message').textContent = message;
}

function clearErrorState() {
    $('#catalog-message').textContent = '';
    setHidden(errorState, true);
}

function gameBadge(card) {
    const labels = { magic: 'Magic', pokemon: 'Pokémon', yugioh: 'Yu-Gi-Oh!' };
    return text('span', labels[card.game] || card.game_label, `game-badge ${gameClass(card.game)}`);
}

function rarityBadge(card) {
    return text('span', rarityLabel(card.rarity), 'rarity-badge');
}

function rarityLabel(value) {
    return rarityLabels[value] || value;
}

function imageBadge(card) {
    return text('span', card.image_source === 'upload' ? 'Arquivo' : card.image_source === 'url' ? 'URL' : 'Sem imagem', `image-source-badge ${card.image_source || ''}`);
}

function createThumb(card) {
    const image = document.createElement('img');
    image.className = 'card-thumb';
    image.src = card.image_url;
    image.alt = `Imagem de ${card.name_en}`;
    image.loading = 'lazy';
    image.addEventListener('error', () => {
        if (!image.src.endsWith('/assets/images/card-fallback.svg')) {
            image.src = '/assets/images/card-fallback.svg';
        }
    }, { once: true });
    return image;
}

function selectCard(id) {
    state.selectedId = id;
    syncUrl();
    if (window.location.pathname.replace(/\/+$/, '') === '/cards') {
        window.location.assign(`/cards/${id}`);
        return;
    }
    renderRows();
    renderInspector();
}

function createTableRow(card) {
    const row = document.createElement('tr');
    row.dataset.cardId = String(card.id);
    const imageCell = text('td', '', 'table-image-cell');
    imageCell.append(createThumb(card));
    row.append(imageCell);

    const englishCell = text('td', card.name_en, 'table-name-cell');
    row.append(englishCell);

    const portugueseCell = text('td', card.name_pt || '—', 'table-secondary-cell');
    row.append(portugueseCell);

    const gameCell = document.createElement('td');
    gameCell.append(gameBadge(card));
    row.append(gameCell);

    row.append(text('td', card.edition_label, 'table-secondary-cell'));

    const rarityCell = document.createElement('td');
    rarityCell.append(rarityBadge(card));
    row.append(rarityCell);

    row.addEventListener('click', (event) => {
        if (!event.target.closest('button')) selectCard(card.id);
    });
    return row;
}

function createMobileCard(card) {
    const article = document.createElement('article');
    article.className = 'mobile-card';
    article.dataset.cardId = String(card.id);
    article.classList.toggle('is-selected', state.selectedId === card.id);
    article.append(createThumb(card));

    const copy = document.createElement('div');
    copy.className = 'mobile-card-copy';
    copy.append(text('strong', card.name_en), text('small', card.name_pt || 'Sem tradução'));
    const meta = document.createElement('div');
    meta.className = 'mobile-card-meta';
    meta.append(gameBadge(card), rarityBadge(card));
    copy.append(meta);
    article.append(copy);

    const actions = document.createElement('div');
    actions.className = 'mobile-card-actions';
    actions.append(
        button('Editar', 'row-action row-action-edit', (event) => {
            event.stopPropagation();
            openDrawer('edit', card);
        }),
        button('Excluir', 'row-action row-action-delete', (event) => {
            event.stopPropagation();
            openDeleteDialog(card);
        }),
    );
    article.append(actions);
    article.addEventListener('click', (event) => {
        if (!event.target.closest('button')) selectCard(card.id);
    });
    return article;
}

function updateSelectAll() {
    const selectAll = $('#select-all');
    if (!selectAll) return;
    selectAll.checked = state.items.length > 0 && state.items.every((card) => state.selectedIds.has(card.id));
    selectAll.indeterminate = state.items.some((card) => state.selectedIds.has(card.id)) && !selectAll.checked;
}

function renderRows() {
    tableBody.replaceChildren(...state.items.map(createTableRow));
    mobileList.replaceChildren(...state.items.map(createMobileCard));
    updateSelectAll();
}

function renderPagination() {
    $('#page-caption').textContent = `Página ${state.page} de ${state.totalPages}`;
    $('#previous-page').disabled = state.page <= 1;
    $('#next-page').disabled = state.page >= state.totalPages;
    const pageButtons = $('#page-buttons');
    pageButtons.replaceChildren();

    const pages = state.totalPages <= 7
        ? Array.from({ length: state.totalPages }, (_, index) => index + 1)
        : state.page <= 3
            ? [1, 2, 3, state.totalPages - 1, state.totalPages]
            : state.page >= state.totalPages - 2
                ? [1, 2, state.totalPages - 2, state.totalPages - 1, state.totalPages]
                : [1, state.page - 1, state.page, state.page + 1, state.totalPages];

    let previousPage = 0;
    pages.forEach((page) => {
        if (page - previousPage > 1) {
            const ellipsis = text('span', '…', 'pagination-ellipsis');
            ellipsis.setAttribute('aria-hidden', 'true');
            pageButtons.append(ellipsis);
        }

        const pageButton = button(String(page), page === state.page ? 'is-active' : '', () => {
            state.page = page;
            syncUrl();
            loadCards(true);
        });
        pageButton.setAttribute('aria-label', `Ir para a página ${page}`);
        pageButton.setAttribute('aria-current', page === state.page ? 'page' : 'false');
        pageButtons.append(pageButton);
        previousPage = page;
    });
}

function renderInspector() {
    if (!inspectorEmpty || !inspectorContent) return;
    const card = state.items.find((item) => item.id === state.selectedId);
    if (!card) {
        inspectorEmpty.hidden = false;
        inspectorContent.hidden = true;
        return;
    }

    inspectorEmpty.hidden = true;
    inspectorContent.hidden = false;
    inspectorContent.replaceChildren();

    const visual = document.createElement('div');
    visual.className = 'inspector-visual';
    visual.append(createThumb(card));
    inspectorContent.append(visual);

    const heading = document.createElement('div');
    heading.className = 'inspector-heading';
    heading.append(text('h2', card.name_en), text('p', card.name_pt || 'Sem tradução cadastrada'));
    inspectorContent.append(heading);

    const badges = document.createElement('div');
    badges.className = 'inspector-badges';
    badges.append(gameBadge(card), rarityBadge(card));
    inspectorContent.append(badges);

    const details = document.createElement('div');
    details.className = 'inspector-details';
    [["Coleção", card.edition_label], ["Imagem", card.image_source === 'upload' ? 'Arquivo local' : card.image_source === 'url' ? 'URL externa' : 'Sem imagem'], ["Atualizado em", formatUpdated(card.updated_at)]].forEach(([label, value]) => {
        const line = document.createElement('div');
        line.className = 'detail-line';
        line.append(text('span', label), text('strong', value));
        details.append(line);
    });
    inspectorContent.append(details);

    const actions = document.createElement('div');
    actions.className = 'inspector-actions';
    actions.append(button('Editar carta', 'button button-primary', () => openDrawer('edit', card)));
    actions.append(button('Excluir', 'button button-secondary', () => openDeleteDialog(card)));
    inspectorContent.append(actions);

    const index = state.items.findIndex((item) => item.id === card.id);
    const navigation = document.createElement('div');
    navigation.className = 'inspector-nav';
    const previous = button('← Anterior', '', () => selectCard(state.items[index - 1].id));
    const next = button('Próxima →', '', () => selectCard(state.items[index + 1].id));
    previous.disabled = index <= 0;
    next.disabled = index >= state.items.length - 1;
    navigation.append(previous, next);
    inspectorContent.append(navigation);
}

function renderCatalog() {
    const hasItems = state.items.length > 0;
    setHidden(tableState, !hasItems);
    setHidden(emptyState, hasItems);
    setHidden(errorState, true);
    setHidden(loadingState, true);
    const counts = state.items.reduce((result, card) => {
        result[card.game] = (result[card.game] || 0) + 1;
        return result;
    }, {});
    const summaryCount = $('#summary-count');
    if (summaryCount) summaryCount.textContent = formatCount(state.total);
    if ($('#summary-magic')) $('#summary-magic').textContent = formatCount(counts.magic || 0);
    if ($('#summary-pokemon')) $('#summary-pokemon').textContent = formatCount(counts.pokemon || 0);
    if ($('#summary-yugioh')) $('#summary-yugioh').textContent = formatCount(counts.yugioh || 0);
    if ($('#toolbar-result')) $('#toolbar-result').textContent = hasItems ? `Exibindo ${state.items.length} de ${formatCount(state.total)}.` : '0 resultados.';
    renderRows();
    renderPagination();
    renderInspector();
}

async function loadCards(keepSelection = false) {
    setLoading(true);
    clearErrorState();
    const params = new URLSearchParams({
        q: state.q,
        game: state.game,
        page: String(state.page),
        per_page: String(state.perPage),
        sort: state.sort,
        direction: state.direction,
    });
    try {
        const response = await get(`/api/cards?${params.toString()}`);
        state.items = response.data?.items || [];
        state.total = Number(response.meta?.total || 0);
        state.totalPages = Math.max(1, Number(response.meta?.total_pages || 1));
        if (!keepSelection || !state.items.some((item) => item.id === state.selectedId)) {
            state.selectedId = state.items[0]?.id || null;
        }
        syncUrl();
        renderCatalog();
    } catch (error) {
        if (error instanceof ApiError && (error.status === 401 || error.status === 419)) return;
        showErrorState(error instanceof ApiError ? error.message : 'Tente novamente em alguns instantes.');
    }
}

function clearFieldErrors() {
    $$('.field-message', form).forEach((node) => { node.textContent = ''; });
    $$('.field-group', form).forEach((node) => node.classList.remove('has-error'));
    $('#card-form').querySelector('.image-fieldset')?.classList.remove('has-error');
}

function showFieldErrors(fields = {}) {
    Object.entries(fields).forEach(([field, message]) => {
        const input = form.querySelector(`[name="${field}"]`);
        const errorNode = form.querySelector(`[data-error-for="${field}"]`);
        input?.closest('.field-group')?.classList.add('has-error');
        if (errorNode) errorNode.textContent = message;
    });
}

function setFormProcessing(processing) {
    const submit = $('#form-submit');
    submit.disabled = processing;
    submit.classList.toggle('is-processing', processing);
}

function setSelectOptions(select, items, placeholder, selected = '') {
    select.replaceChildren(text('option', placeholder));
    items.forEach((item) => {
        const value = item.id || item;
        const label = item.name || (select.id === 'field-rarity' ? rarityLabel(value) : value);
        const option = text('option', label);
        option.value = value;
        option.selected = option.value === selected;
        select.append(option);
    });
}

async function loadOptions(game, edition = '', rarity = '') {
    const requestId = ++state.optionsRequest;
    const editionSelect = $('#field-edition');
    const raritySelect = $('#field-rarity');
    if (!game) {
        setSelectOptions(editionSelect, [], 'Escolha o jogo primeiro');
        setSelectOptions(raritySelect, [], 'Escolha o jogo primeiro');
        editionSelect.disabled = true;
        raritySelect.disabled = true;
        return;
    }
    setSelectOptions(editionSelect, [], 'Carregando coleções...');
    setSelectOptions(raritySelect, [], 'Carregando raridades...');
    editionSelect.disabled = true;
    raritySelect.disabled = true;
    try {
        const response = await get(`/api/catalog/options?game=${encodeURIComponent(game)}`);
        if (requestId !== state.optionsRequest) return;
        setSelectOptions(editionSelect, response.data?.editions || [], 'Escolha uma coleção', edition);
        setSelectOptions(raritySelect, response.data?.rarities || [], 'Escolha uma raridade', rarity);
        editionSelect.disabled = false;
        raritySelect.disabled = false;
    } catch (error) {
        if (requestId !== state.optionsRequest) return;
        setSelectOptions(editionSelect, [], 'Não foi possível carregar');
        setSelectOptions(raritySelect, [], 'Não foi possível carregar');
        $('#catalog-message').textContent = error instanceof ApiError ? error.message : 'Não foi possível carregar as opções.';
    }
}

function updateImageSourcePanels() {
    const source = $('input[name="image_source"]:checked', form)?.value || 'upload';
    $('#upload-panel').hidden = source !== 'upload';
    $('#url-panel').hidden = source !== 'url';
    $$('.source-tab', form).forEach((tab) => tab.classList.toggle('is-active', tab.querySelector('input').checked));
    updateUploadZoneLabel();
}

function updateUploadZoneLabel(fileName = '') {
    const file = $('#field-image')?.files?.[0];
    const label = $('#upload-zone-label');
    const help = $('#upload-zone-help');
    const name = fileName || file?.name || '';
    if (label) label.textContent = name || 'Escolha uma imagem';
    if (help) help.hidden = Boolean(name);
}

function clearImageFieldErrors() {
    form.querySelectorAll('[data-error-for="image"], [data-error-for="image_value"]').forEach((node) => {
        node.textContent = '';
    });
    form.querySelector('.image-fieldset')?.classList.remove('has-error');
}

function setPreview(src, name = 'Prévia da imagem') {
    const preview = $('#image-preview');
    const image = $('#image-preview-img');
    if (!src) {
        preview.hidden = true;
        image.src = '/assets/images/card-fallback.svg';
        $('#image-preview-name').textContent = '';
        return;
    }
    image.src = src;
    image.onerror = () => { image.src = '/assets/images/card-fallback.svg'; };
    $('#image-preview-name').textContent = name;
    preview.hidden = false;
}

function revokeImageObjectUrl() {
    if (state.imageObjectUrl) {
        URL.revokeObjectURL(state.imageObjectUrl);
        state.imageObjectUrl = '';
    }
}

function resetForm() {
    form.reset();
    $('#form-card-id').value = '';
    $('#field-edition').disabled = true;
    $('#field-rarity').disabled = true;
    setSelectOptions($('#field-edition'), [], 'Escolha o jogo primeiro');
    setSelectOptions($('#field-rarity'), [], 'Escolha o jogo primeiro');
    clearFieldErrors();
    revokeImageObjectUrl();
    setPreview('');
    updateUploadZoneLabel();
    state.imageCleared = false;
    state.dirty = false;
    updateImageSourcePanels();
}

async function openDrawer(mode, card = null) {
    resetForm();
    state.drawerMode = mode;
    state.currentCard = card;
    $('#drawer-kicker').textContent = mode === 'edit' ? 'EDITAR CARTA' : 'NOVA CARTA';
    $('#drawer-title').textContent = mode === 'edit' ? 'Editar carta' : 'Adicionar carta';
    if (card) {
        $('#form-card-id').value = String(card.id);
        $('#field-name-en').value = card.name_en;
        $('#field-name-pt').value = card.name_pt || '';
        $('#field-game').value = card.game;
        $('#field-image-url').value = card.image_source === 'url' ? card.image_value || '' : '';
        const source = $(`input[name="image_source"][value="${card.image_source || 'upload'}"]`, form);
        if (source) source.checked = true;
        setPreview(card.image_url, card.name_en);
        loadOptions(card.game, card.edition_id, card.rarity);
    }
    updateImageSourcePanels();
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    window.setTimeout(() => $('#field-name-en').focus(), 40);
}

async function openRequestedEdit() {
    const requestedId = state.editId;
    if (state.newRequested) {
        state.newRequested = false;
        state.editId = null;
        syncUrl();
        await openDrawer('create');
        return;
    }
    if (!requestedId) return;
    let card = state.items.find((item) => item.id === requestedId) || null;
    if (!card) {
        try {
            card = (await get(`/api/cards/${requestedId}`)).data?.item || null;
        } catch {
            return;
        }
    }
    if (card) {
        state.editId = null;
        syncUrl();
        await openDrawer('edit', card);
    }
}

function closeDrawer() {
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    revokeImageObjectUrl();
    state.dirty = false;
    state.currentCard = null;
}

function validateForm() {
    clearFieldErrors();
    const fields = {};
    if (!$('#field-name-en').value.trim()) fields.name_en = 'Informe o nome em inglês.';
    if (!$('#field-game').value) fields.game = 'Escolha um jogo.';
    if (!$('#field-edition').value) fields.edition_id = 'Escolha uma coleção.';
    if (!$('#field-rarity').value) fields.rarity = 'Escolha uma raridade.';
    const source = $('input[name="image_source"]:checked', form)?.value;
    if (source === 'url' && !$('#field-image-url').value.trim()) fields.image_value = 'Informe a URL da imagem.';
    if (source === 'upload' && state.drawerMode === 'create' && !$('#field-image').files.length) fields.image = 'Escolha uma imagem ou use uma URL.';
    if (Object.keys(fields).length) {
        showFieldErrors(fields);
        return false;
    }
    return true;
}

async function submitForm(event) {
    event.preventDefault();
    if (!validateForm()) return;
    setFormProcessing(true);
    const data = new FormData(form);
    data.delete('card_id');
    if (state.drawerMode === 'edit') {
        data.append('_method', 'PUT');
        if (state.currentCard?.image_value && !state.imageCleared && !$('#field-image').files.length && $('input[name="image_source"]:checked', form)?.value === 'upload') {
            data.append('retain_image', '1');
        }
        if (state.imageCleared) data.append('clear_image', '1');
    }
    const endpoint = state.drawerMode === 'edit' ? `/api/cards/${state.currentCard.id}` : '/api/cards';
    try {
        const response = await post(endpoint, data);
        const saved = response.data?.item;
        closeDrawer();
        state.selectedId = saved?.id || state.selectedId;
        syncUrl();
        showToast(state.drawerMode === 'edit' ? 'Carta atualizada.' : 'Carta adicionada.');
        await loadCards(true);
    } catch (error) {
        if (error instanceof ApiError) showFieldErrors(error.fields);
        $('#catalog-message').textContent = error instanceof ApiError ? error.message : 'Não foi possível salvar a carta.';
    } finally {
        setFormProcessing(false);
    }
}

function openDeleteDialog(card) {
    state.deleteId = card.id;
    $('#delete-card-name').textContent = card.name_en;
    if (typeof deleteDialog.showModal === 'function') deleteDialog.showModal();
    else deleteDialog.setAttribute('open', '');
    $('#delete-confirm').focus();
}

function closeDeleteDialog() {
    if (typeof deleteDialog.close === 'function') deleteDialog.close();
    else deleteDialog.removeAttribute('open');
    state.deleteId = null;
}

function setFiltersOpen(isOpen) {
    const filters = $('#catalog-filters');
    const toggle = $('#filter-toggle');
    if (!filters || !toggle) return;

    window.clearTimeout(filterCloseTimer);

    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.classList.toggle('is-active', isOpen);
    if (isOpen) {
        filters.hidden = false;
        filters.classList.remove('is-closing');
        filters.classList.add('is-open', 'is-opening');
        window.requestAnimationFrame(() => {
            if (filters.classList.contains('is-open')) filters.classList.remove('is-opening');
        });
        return;
    }

    filters.classList.remove('is-open', 'is-opening');
    filters.classList.add('is-closing');
    const finishClose = () => {
        if (!filters.classList.contains('is-closing')) return;
        filters.hidden = true;
        filters.classList.remove('is-closing');
    };
    const filterTransitionHandler = (event) => {
        if (event.target !== filters || event.propertyName !== 'opacity') return;
        filters.removeEventListener('transitionend', filterTransitionHandler);
        window.clearTimeout(filterCloseTimer);
        finishClose();
    };
    filters.addEventListener('transitionend', filterTransitionHandler);
    filterCloseTimer = window.setTimeout(() => {
        filters.removeEventListener('transitionend', filterTransitionHandler);
        finishClose();
    }, 220);
}

async function confirmDelete() {
    if (!state.deleteId) return;
    const confirmButton = $('#delete-confirm');
    confirmButton.disabled = true;
    confirmButton.classList.add('is-processing');
    try {
        await remove(`/api/cards/${state.deleteId}`);
        closeDeleteDialog();
        state.selectedId = null;
        syncUrl();
        showToast('Carta excluída.');
        await loadCards(false);
    } catch (error) {
        showToast(error instanceof ApiError ? error.message : 'Não foi possível excluir a carta.', true);
    } finally {
        confirmButton.disabled = false;
        confirmButton.classList.remove('is-processing');
    }
}

function showToast(message, isError = false) {
    const toast = text('div', message, `toast${isError ? ' is-error' : ''}`);
    $('#toast-region').append(toast);
    window.setTimeout(() => toast.remove(), 3600);
}

function bindEvents() {
    $('#new-card-button')?.addEventListener('click', () => openDrawer('create'));
    $('#retry-action').addEventListener('click', () => loadCards(true));
    $('#filter-toggle')?.addEventListener('click', () => {
        const filters = $('#catalog-filters');
        if (!filters) return;
        setFiltersOpen(!filters.classList.contains('is-open'));
    });
    $('#drawer-close').addEventListener('click', () => closeDrawer());
    $('#drawer-backdrop').addEventListener('click', () => closeDrawer());
    $('#form-cancel').addEventListener('click', () => closeDrawer());
    form.addEventListener('submit', submitForm);
    form.addEventListener('input', () => { state.dirty = true; });
    form.addEventListener('change', () => { state.dirty = true; });
    $('#field-game').addEventListener('change', () => {
        state.dirty = true;
        loadOptions($('#field-game').value);
    });
    $$('input[name="image_source"]', form).forEach((input) => input.addEventListener('change', updateImageSourcePanels));
    $('#field-image').addEventListener('change', () => {
        const file = $('#field-image').files[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            showFieldErrors({ image: 'Use JPG, PNG ou WebP de até 5 MB.' });
            $('#field-image').value = '';
            updateUploadZoneLabel();
            return;
        }
        revokeImageObjectUrl();
        state.imageObjectUrl = URL.createObjectURL(file);
        state.imageCleared = false;
        clearImageFieldErrors();
        updateUploadZoneLabel(file.name);
    });
    $('#field-image-url').addEventListener('input', () => {
        const url = $('#field-image-url').value.trim();
        if (url.startsWith('http://') || url.startsWith('https://')) setPreview(url, 'Imagem por URL');
    });
    $('#remove-image').addEventListener('click', () => {
        state.imageCleared = true;
        $('#field-image').value = '';
        $('#field-image-url').value = '';
        setPreview('');
        updateUploadZoneLabel();
        clearImageFieldErrors();
        state.dirty = true;
    });
    $('#select-all')?.addEventListener('change', (event) => {
        state.items.forEach((card) => event.target.checked ? state.selectedIds.add(card.id) : state.selectedIds.delete(card.id));
        renderRows();
    });
    $('#previous-page').addEventListener('click', () => {
        if (state.page <= 1) return;
        state.page -= 1;
        syncUrl();
        loadCards(true);
    });
    $('#next-page').addEventListener('click', () => {
        if (state.page >= state.totalPages) return;
        state.page += 1;
        syncUrl();
        loadCards(true);
    });
    $('#game-filter').addEventListener('change', (event) => {
        state.game = event.target.value;
        state.page = 1;
        syncUrl();
        loadCards(false);
    });
    $('#sort-filter').addEventListener('change', (event) => {
        [state.sort, state.direction] = event.target.value.split(':');
        state.page = 1;
        syncUrl();
        loadCards(true);
    });
    $('#reset-filters')?.addEventListener('click', () => {
        state.q = '';
        state.game = '';
        state.page = 1;
        state.sort = 'updated';
        state.direction = 'desc';
        $('#search-input').value = '';
        $('#game-filter').value = '';
        $('#sort-filter').value = 'updated:desc';
        syncUrl();
        loadCards(false);
    });
    $('#search-input').addEventListener('input', (event) => {
        state.q = event.target.value.trim();
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(() => {
            state.page = 1;
            syncUrl();
            loadCards(false);
        }, 280);
    });
    $('#logout-button').addEventListener('click', async () => {
        try {
            await post('/api/auth/logout', {});
        } finally {
            window.location.replace('/login');
        }
    });
    $('#delete-close').addEventListener('click', closeDeleteDialog);
    $('#delete-cancel').addEventListener('click', closeDeleteDialog);
    $('#delete-confirm').addEventListener('click', confirmDelete);
    window.addEventListener('popstate', () => {
        readUrlState();
        loadCards(true);
    });
    window.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            $('#search-input').focus();
        }
        if (event.key === 'Escape' && drawer.classList.contains('is-open')) closeDrawer();
    });
    window.addEventListener('cardmancer:session-expired', () => {
        window.location.replace('/login?expired=1');
    }, { once: true });
}

async function init() {
    bindEvents();
    readUrlState();
    try {
        const session = await getSession();
        if (!session.data?.authenticated) {
            window.location.replace('/login');
            return;
        }
        setCsrfToken(session.data.csrf_token);
        await loadCards(true);
        await openRequestedEdit();
    } catch (error) {
        if (error instanceof ApiError && error.status === 401) window.location.replace('/login');
        else showErrorState('Atualize a página e tente novamente.');
    }
}

init();
