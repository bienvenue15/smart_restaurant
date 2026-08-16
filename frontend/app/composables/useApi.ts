/**
 * Thin fetch wrapper around the Express API. Always sends credentials
 * (httpOnly cookies for the customer table-session / refresh token) and
 * attaches the staff access token when present — see useAuth.ts.
 */
export function useApi() {
  const config = useRuntimeConfig();
  const auth = useAuthStore();

  async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
    const headers = new Headers(options.headers);
    headers.set('Content-Type', 'application/json');
    if (auth.accessToken.value) {
      headers.set('Authorization', `Bearer ${auth.accessToken.value}`);
    }

    const response = await fetch(`${config.public.apiBaseUrl}${path}`, {
      ...options,
      headers,
      credentials: 'include',
    });

    const body = await response.json().catch(() => null);

    if (!response.ok) {
      throw new Error(body?.message ?? `Request failed: ${response.status}`);
    }

    return body?.data as T;
  }

  // Separate from `request` because a multipart body must NOT get the
  // `Content-Type: application/json` header `request` always sets — the
  // browser needs to set its own multipart boundary.
  async function upload<T>(path: string, file: File, fieldName = 'image'): Promise<T> {
    const headers = new Headers();
    if (auth.accessToken.value) headers.set('Authorization', `Bearer ${auth.accessToken.value}`);

    const formData = new FormData();
    formData.append(fieldName, file);

    const response = await fetch(`${config.public.apiBaseUrl}${path}`, {
      method: 'POST',
      headers,
      body: formData,
      credentials: 'include',
    });

    const body = await response.json().catch(() => null);
    if (!response.ok) throw new Error(body?.message ?? `Upload failed: ${response.status}`);
    return body?.data as T;
  }

  return {
    get: <T>(path: string) => request<T>(path),
    post: <T>(path: string, data?: unknown) => request<T>(path, { method: 'POST', body: JSON.stringify(data) }),
    patch: <T>(path: string, data?: unknown) => request<T>(path, { method: 'PATCH', body: JSON.stringify(data) }),
    del: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
    upload,
  };
}
