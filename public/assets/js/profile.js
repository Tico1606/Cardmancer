import { ApiError, post } from './api.js';

const form = document.querySelector('#profile-form');
const submit = document.querySelector('#profile-submit');
const nameInput = document.querySelector('#profile-name');
const emailInput = document.querySelector('#profile-email');
const newPasswordInput = document.querySelector('#profile-new-password');
const confirmPasswordInput = document.querySelector('#profile-confirm-password');
const feedback = document.querySelector('#profile-feedback');

function clearErrors() {
    form.querySelectorAll('.profile-field-message').forEach((node) => {
        node.textContent = '';
    });
    form.querySelectorAll('.profile-field').forEach((node) => {
        node.classList.remove('has-error');
    });
    form.querySelectorAll('[aria-invalid="true"]').forEach((node) => {
        node.removeAttribute('aria-invalid');
    });
    feedback.textContent = '';
    feedback.classList.remove('is-success');
}

function showErrors(fields = {}) {
    Object.entries(fields).forEach(([field, message]) => {
        const input = form.querySelector(`[name="${field}"]`);
        const messageNode = form.querySelector(`[data-error-for="${field}"]`);
        input?.closest('.profile-field')?.classList.add('has-error');
        input?.setAttribute('aria-invalid', 'true');
        if (messageNode) messageNode.textContent = message;
    });
}

function setProcessing(processing) {
    submit.disabled = processing;
    submit.classList.toggle('is-processing', processing);
}

function validate() {
    const fields = {};
    if (!nameInput.value.trim()) fields.name = 'Informe seu nome.';
    if (!emailInput.value.trim()) {
        fields.email = 'Informe seu e-mail.';
    } else if (!emailInput.validity.valid) {
        fields.email = 'Informe um e-mail válido.';
    }
    if (newPasswordInput.value || confirmPasswordInput.value) {
        if (newPasswordInput.value.length < 8) fields.new_password = 'Use pelo menos 8 caracteres.';
        if (newPasswordInput.value !== confirmPasswordInput.value) fields.confirm_password = 'As senhas não coincidem.';
    }
    if (Object.keys(fields).length) {
        showErrors(fields);
        return false;
    }
    return true;
}

function togglePasswordVisibility(event) {
    const toggle = event.currentTarget;
    const input = document.querySelector(`#${toggle.dataset.passwordToggle}`);
    if (!input) return;
    const isVisible = input.getAttribute('type') === 'password';
    input.setAttribute('type', isVisible ? 'text' : 'password');
    toggle.setAttribute('aria-label', isVisible ? 'Ocultar senha' : 'Mostrar senha');
    toggle.setAttribute('aria-pressed', String(isVisible));
    toggle.classList.toggle('is-visible', isVisible);
    toggle.querySelector('[data-password-icon="open"]')?.toggleAttribute('hidden', isVisible);
    toggle.querySelector('[data-password-icon="closed"]')?.toggleAttribute('hidden', !isVisible);
}

function updateSummary(user) {
    const name = user.name || 'Administrador';
    const email = user.email || 'Sem e-mail';
    const initials = name.trim().split(/\s+/).map((part) => part[0]).slice(0, 2).join('').toUpperCase();
    document.querySelector('#profile-summary-name').textContent = name;
    document.querySelector('#profile-summary-email').textContent = email;
    document.querySelector('.profile-summary-avatar').textContent = initials || 'AD';
    document.querySelector('.sidebar-profile-avatar').textContent = initials || 'AD';
    document.querySelector('.sidebar-profile-copy strong').textContent = name;
    document.querySelector('.sidebar-profile-copy small').textContent = email;
    document.querySelector('.management-topbar > span').textContent = `Meu perfil: ${name}`;
}

form.addEventListener('reset', () => {
    window.setTimeout(clearErrors, 0);
});

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();
    if (!validate()) return;

    setProcessing(true);
    try {
        const response = await post('/api/profile', {
            name: nameInput.value.trim(),
            email: emailInput.value.trim(),
            new_password: newPasswordInput.value,
            confirm_password: confirmPasswordInput.value,
        });
        const user = response.data?.user || {};
        nameInput.value = user.name || nameInput.value;
        emailInput.value = user.email || emailInput.value;
        newPasswordInput.value = '';
        confirmPasswordInput.value = '';
        updateSummary(user);
        feedback.textContent = 'Perfil atualizado com sucesso.';
        feedback.classList.add('is-success');
    } catch (error) {
        if (error instanceof ApiError && error.fields) showErrors(error.fields);
        feedback.textContent = error instanceof ApiError ? error.message : 'Não foi possível atualizar o perfil.';
    } finally {
        setProcessing(false);
    }
});

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => toggle.addEventListener('click', togglePasswordVisibility));

document.querySelector('#logout-button')?.addEventListener('click', async () => {
    try {
        await post('/api/auth/logout', {});
    } finally {
        window.location.replace('/login');
    }
});

window.addEventListener('cardmancer:session-expired', () => window.location.replace('/login?expired=1'), { once: true });
