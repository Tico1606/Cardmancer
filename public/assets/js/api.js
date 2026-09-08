let csrfToken = document.body?.dataset.csrfToken || '';

export class ApiError extends Error {
    constructor(message, status = 0, code = 'request_error', fields = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.code = code;
        this.fields = fields;
    }
}

export function setCsrfToken(token) {
    csrfToken = token || '';
    if (document.body) {
        document.body.dataset.csrfToken = csrfToken;
    }
}

export async function request(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = new Headers(options.headers || {});
    headers.set('Accept', 'application/json');

    let body = options.body;
    if (body && !(body instanceof FormData) && typeof body === 'object') {
        headers.set('Content-Type', 'application/json');
        body = JSON.stringify(body);
    }

    if (!['GET', 'HEAD'].includes(method) && csrfToken) {
        headers.set('X-CSRF-Token', csrfToken);
    }

    const response = await fetch(path, {
        ...options,
        method,
        headers,
        body,
        credentials: 'same-origin',
    });

    let payload = null;
    try {
        payload = await response.json();
    } catch {
        payload = null;
    }

    if (!response.ok || payload?.error) {
        const error = payload?.error || {};
        const apiError = new ApiError(
            error.message || 'Não foi possível concluir a solicitação.',
            response.status,
            error.code || 'request_error',
            error.fields || {},
        );

        if (response.status === 401 || response.status === 419) {
            window.dispatchEvent(new CustomEvent('cardmancer:session-expired'));
        }

        throw apiError;
    }

    return payload || { data: null, error: null, meta: {} };
}

export function getSession() {
    return request('/api/auth/session');
}

export function get(path, options = {}) {
    return request(path, { ...options, method: 'GET' });
}

export function post(path, body, options = {}) {
    return request(path, { ...options, method: 'POST', body });
}

export function remove(path, options = {}) {
    return request(path, { ...options, method: 'DELETE' });
}
