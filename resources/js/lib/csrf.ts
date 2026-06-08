/**
 * Ambil CSRF token untuk request fetch/axios di SPA Inertia.
 * Prioritas: prop Inertia (selalu segar) → meta tag → cookie XSRF-TOKEN.
 */
export function getCsrfToken(inertiaToken?: string): string {
    if (inertiaToken) {
        return inertiaToken;
    }

    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    if (meta) {
        return meta;
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    if (match?.[1]) {
        return decodeURIComponent(match[1]);
    }

    return '';
}
