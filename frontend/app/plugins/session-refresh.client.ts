/**
 * The root cause of "session expired, have to refresh the page" reports:
 * the access token only lasts 15 minutes (JWT_ACCESS_TTL) and previously
 * nothing renewed it while the app stayed open in one SPA session — only a
 * full page reload (auth-restore.ts) traded the refresh cookie for a new
 * one. Any staff member active past 15 minutes hit a wall of failed
 * requests with no recovery short of reloading.
 *
 * This watches the access token and schedules a silent refresh shortly
 * before it actually expires, for as long as a session is active — so the
 * token is renewed in the background and a real expiry is never reached
 * during normal use. useApi.ts's 401-retry is the remaining safety net for
 * clock drift or a laptop that slept through the timer.
 */
export default defineNuxtPlugin(() => {
  const auth = useAuthStore();
  let timer: ReturnType<typeof setTimeout> | null = null;

  function clear() {
    if (timer) clearTimeout(timer);
    timer = null;
  }

  function schedule(token: string) {
    clear();
    const expiryMs = decodeJwtExpiryMs(token);
    if (expiryMs === null) return;
    // Refresh 60s before expiry; never schedule sooner than 5s out (avoids
    // a tight retry loop if the token is already near/past expiry).
    const delay = Math.max(expiryMs - Date.now() - 60_000, 5_000);
    timer = setTimeout(async () => {
      const ok = await auth.refreshSession();
      if (ok && auth.accessToken.value) schedule(auth.accessToken.value);
    }, delay);
  }

  watch(
    auth.accessToken,
    (token) => {
      if (token) schedule(token);
      else clear();
    },
    { immediate: true },
  );
});
