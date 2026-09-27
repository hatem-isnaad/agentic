import type { AdminBoot } from './config';

type JsonMap = Record<string, unknown>;

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
    const json = await res.json().catch(() => ({}));

    if (!res.ok) {
        const message = (json as JsonMap).message ?? res.statusText;
        throw new Error(String(message));
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
