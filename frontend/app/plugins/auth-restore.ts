/**
 * The staff access token lives only in memory (useAuthStore), so a full
 * page reload or a direct link to a /staff or /admin route always starts
 * with no session — the staff-auth/admin-auth middleware would otherwise
 * bounce straight to /staff/login even when the httpOnly refresh cookie
 * is still valid. This plugin trades that cookie for a fresh access token
 * before route middleware runs, on the two request shapes that lose
 * in-memory state: an SSR request (cookie read from the incoming request)
 * and a client-only cold load (cookie sent automatically by the browser).
 */
export default defineNuxtPlugin(async () => {
  const route = useRoute();
  const adminPath = useAdminPath();
  if (!route.path.startsWith('/staff') && !adminPath.isAdminRoute(route.path)) return;

  const auth = useAuthStore();
  if (auth.accessToken.value) return;

  const headers = new Headers();
  if (import.meta.server) {
    const forwarded = useRequestHeaders(['cookie']);
    if (forwarded.cookie) headers.set('cookie', forwarded.cookie);
  }

  // No valid refresh cookie (or the API is unreachable) — stay logged out
  // and let the route middleware redirect to /staff/login as usual.
  await auth.refreshSession(headers);
});
