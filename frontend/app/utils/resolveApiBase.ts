/**
 * `public.apiBaseUrl` is a relative path in dev ("/api/v1", proxied to the
 * backend by Vite/Nitro) so the browser never talks to the backend's port
 * directly. That relative path only resolves against a page origin though —
 * Node's fetch() has none during SSR, so a server-side call with it throws
 * ("Failed to parse URL from /api/v1/..."). auth-restore.ts hitting this on
 * every hard refresh of a /staff or /admin page was silently failing to
 * restore the session server-side, bouncing straight to /staff/login even
 * with a valid refresh cookie. Route SSR-side calls straight to the backend
 * origin instead; client-side calls keep using the relative, proxied path.
 */
export function resolveApiBase(): string {
  const config = useRuntimeConfig();
  const base = config.public.apiBaseUrl as string;
  if (import.meta.server && base.startsWith('/')) {
    return `${config.apiOrigin}${base}`;
  }
  return base;
}
