import { ApiError, getSession, post, setCsrfToken } from './api.js';

const form = document.querySelector('#login-form');
const submit = document.querySelector('#login-submit');
const feedback = document.querySelector('#login-feedback');
const emailInput = document.querySelector('#email');
const passwordInput = document.querySelector('#password');
const passwordToggle = document.querySelector('#password-toggle');

function togglePasswordVisibility() {
    if (!passwordInput || !passwordToggle) return;
    const isVisible = passwordInput.getAttribute('type') === 'password';
    passwordInput.setAttribute('type', isVisible ? 'text' : 'password');
    passwordToggle.setAttribute('aria-label', isVisible ? 'Ocultar senha' : 'Mostrar senha');
    passwordToggle.setAttribute('aria-pressed', String(isVisible));
    passwordToggle.classList.toggle('is-visible', isVisible);
    passwordToggle.querySelector('[data-password-icon="open"]')?.toggleAttribute('hidden', isVisible);
    passwordToggle.querySelector('[data-password-icon="closed"]')?.toggleAttribute('hidden', !isVisible);
}

function setProcessing(processing) {
    submit.disabled = processing;
    submit.classList.toggle('is-processing', processing);
}

function clearErrors() {
    form.querySelectorAll('.field-message').forEach((node) => {
        node.textContent = '';
    });
    form.querySelectorAll('.field-group').forEach((node) => {
        node.classList.remove('has-error');
    });
    feedback.textContent = '';
    feedback.classList.remove('is-success');
}

function showErrors(fields = {}) {
    Object.entries(fields).forEach(([field, message]) => {
        const input = form.querySelector(`[name="${field}"]`);
        const messageNode = form.querySelector(`[data-error-for="${field}"]`);
        input?.closest('.field-group')?.classList.add('has-error');
        if (messageNode) {
            messageNode.textContent = message;
        }
    });
}

function validate() {
    const fields = {};
    if (!emailInput.value.trim()) {
        fields.email = 'Informe seu e-mail.';
    } else if (!emailInput.validity.valid) {
        fields.email = 'Informe um e-mail válido.';
    }
    if (!passwordInput.value) {
        fields.password = 'Informe sua senha.';
    }
    if (Object.keys(fields).length) {
        showErrors(fields);
        return false;
    }
    return true;
}

async function checkExistingSession() {
    try {
        const response = await getSession();
        if (response.data?.authenticated) {
            window.location.replace('/cards');
        }
    } catch {
        // The login form remains usable when the initial session check fails.
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();
    if (!validate()) {
        return;
    }

    setProcessing(true);
    try {
        const response = await post('/api/auth/login', {
            email: emailInput.value.trim(),
            password: passwordInput.value,
        });
        setCsrfToken(response.data?.csrf_token);
        feedback.textContent = 'Acesso confirmado. Abrindo o catálogo...';
        feedback.classList.add('is-success');
        window.setTimeout(() => window.location.replace('/cards'), 120);
    } catch (error) {
        if (error instanceof ApiError && error.fields) {
            showErrors(error.fields);
        }
        feedback.textContent = error instanceof ApiError ? error.message : 'Não foi possível entrar. Tente novamente.';
    } finally {
        setProcessing(false);
    }
});

passwordToggle?.addEventListener('click', togglePasswordVisibility);
checkExistingSession();
