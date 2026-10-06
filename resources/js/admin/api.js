function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/**
 * Call the admin JSON API and turn validation failures into readable errors.
 */
export async function request(url, options = {}) {
    const isFormData = options.body instanceof FormData;
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
            'X-CSRF-TOKEN': csrfToken(),
            ...options.headers,
        },
    });

    if (response.status === 401 || response.status === 419) {
        window.location.href = '/admin/login';
        throw new Error('Your session has ended. Please sign in again.');
    }

    let body = {};
    try {
        body = await response.json();
    } catch {
        throw new Error('The server returned an unreadable response. Please refresh and try again.');
    }

    if (!response.ok) {
        const validationError = Object.values(body.errors ?? {})[0]?.[0];
        throw new Error(validationError || body.message || 'The request could not be completed.');
    }

    return body;
}

export async function uploadImage(file) {
    const form = new FormData();
    form.append('image', file);
    const result = await request('/admin/api/media', { method: 'POST', body: form });

    return result.url;
}

export { csrfToken };
