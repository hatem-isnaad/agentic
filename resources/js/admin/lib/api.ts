import type { AdminBoot } from './config';

type JsonMap = Record<string, unknown>;

export class AdminApiError extends Error {
    readonly status: number;

    constructor(message: string, status: number) {
        super(message);
        this.name = 'AdminApiError';
        this.status = status;
    }
}

function friendlyMessage(status: number, json: JsonMap, fallback: string): string {
    const raw = typeof json.message === 'string' ? json.message.trim() : '';
    if (raw !== '' && raw !== 'This action is unauthorized.' && raw !== 'Unauthenticated.') {
        return raw;
    }
    if (status === 401) {
        return 'Please sign in to access Agentic admin.';
    }
    if (status === 403) {
        return 'You do not have permission to access Agentic admin.';
    }
    if (status === 429) {
        return 'Too many requests. Please wait a moment and try again.';
    }

    return fallback;
}

async function request<T>(
    boot: AdminBoot,
    path: string,
    options: RequestInit = {},
): Promise<T> {
    const url = `${boot.apiPrefix}${path.startsWith('/') ? path : `/${path}`}`;
    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');
    headers.set('X-Agentic-Locale', boot.locale);
    const isFormData = typeof FormData !== 'undefined' && options.body instanceof FormData;
    if (options.body && !isFormData && !headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json');
    }

    const res = await fetch(url, { ...options, headers, credentials: 'same-origin' });
    const json = (await res.json().catch(() => ({}))) as JsonMap;

    if (!res.ok) {
        const message = friendlyMessage(res.status, json, res.statusText || 'Request failed');
        if (typeof window !== 'undefined' && (res.status === 401 || res.status === 403)) {
            window.dispatchEvent(
                new CustomEvent('agentic:admin-auth-error', { detail: { status: res.status, message } }),
            );
        }
        throw new AdminApiError(message, res.status);
    }

    return json as T;
}

export const adminApi = {
    get: <T>(boot: AdminBoot, path: string) => request<T>(boot, path),
    post: <T>(boot: AdminBoot, path: string, body: unknown) =>
        request<T>(boot, path, { method: 'POST', body: JSON.stringify(body) }),
    postForm: <T>(boot: AdminBoot, path: string, body: FormData) =>
        request<T>(boot, path, { method: 'POST', body }),
    put: <T>(boot: AdminBoot, path: string, body: unknown) =>
        request<T>(boot, path, { method: 'PUT', body: JSON.stringify(body) }),
    delete: <T>(boot: AdminBoot, path: string) => request<T>(boot, path, { method: 'DELETE' }),
};

export type Paginated<T> = { data: T[]; meta: JsonMap };
