/**
 * Thin fetch wrapper around the Express API. Always sends credentials
 * (httpOnly cookies for the customer table-session / refresh token) and
 * attaches the staff access token when present — see useAuth.ts.
 *
 * Mutating calls (POST/PUT/PATCH/DELETE/upload) show a success/failure
 * popup unless `{ silent: true }` — background polls and page-load POSTs
 * opt out. Pass `successMessage` for a specific "it worked" line, or
 * `false` when the caller will toast itself after inspecting the result.
 */
export type ApiCallOptions = {
  silent?: boolean;
  successMessage?: string | false;
};

function isMutation(method?: string) {
  const m = (method ?? 'GET').toUpperCase();
  return m === 'POST' || m === 'PUT' || m === 'PATCH' || m === 'DELETE';
}

export function useApi() {
  const apiBase = resolveApiBase();
  const auth = useAuthStore();
  // useApi() is called from setInterval ticks, SSE handlers, and click
  // handlers — not just synchronously during setup() — so it can't use
  // useI18n(), which throws outside that context. $i18n is the same
  // instance without that restriction.
  const { t } = useNuxtApp().$i18n;

  function looksTechnical(message: string) {
    return /failed to fetch|networkerror|err_connection|request failed|econnrefused|timeout|internal server|prisma|sql|unauthorized|forbidden|not found/i.test(
      message,
    );
  }

  function userMessage(status: number | null, serverMessage: string | null) {
    if (status === null) return t('common.offline');
    if (status === 401) {
      return serverMessage && !looksTechnical(serverMessage) ? serverMessage : t('auth.failed');
    }
    if (status === 403) return t('common.forbidden');
    if (status === 404) return t('common.notFound');
    if (status === 429) return t('common.tooMany');
    if (status === 503) return t('common.maintenance');
    if (serverMessage && !looksTechnical(serverMessage)) return serverMessage;
    return t('common.error');
  }

  function notify(ok: boolean, method: string | undefined, callOptions: ApiCallOptions, errorMessage?: string) {
    if (callOptions.silent || !isMutation(method) || !import.meta.client) return;
    const toast = useToast();
    if (ok) {
      if (callOptions.successMessage === false) return;
      toast.success(callOptions.successMessage || t('common.actionSucceeded'));
      return;
    }
    toast.error(t('common.actionFailed'), errorMessage || t('common.error'));
  }

  async function request<T>(
    path: string,
    options: RequestInit = {},
    callOptions: ApiCallOptions = {},
    isRetry = false,
  ): Promise<T> {
    // Only staff/admin requests carry this Bearer token — customer pages
    // authenticate via a separate httpOnly cookie (customerSession.routes.ts)
    // and never reach this branch, so a 401 there is never retried here.
    const wasStaffAuthed = Boolean(auth.accessToken.value);
    const headers = new Headers(options.headers);
    headers.set('Content-Type', 'application/json');
    if (auth.accessToken.value) {
      headers.set('Authorization', `Bearer ${auth.accessToken.value}`);
    }

    let response: Response;
    try {
      response = await fetch(`${apiBase}${path}`, {
        ...options,
        headers,
        credentials: 'include',
      });
    } catch {
      const msg = userMessage(null, null);
      notify(false, options.method, callOptions, msg);
      throw new Error(msg);
    }

    // Safety net for the proactive refresh in plugins/session-refresh.client.ts
    // (clock drift, a laptop asleep through the scheduled timer, etc.) — the
    // access token expired mid-session; try once to renew it and replay this
    // exact request before giving up and surfacing an error.
    if (response.status === 401 && wasStaffAuthed && !isRetry) {
      const refreshed = await auth.refreshSession();
      if (refreshed) return request<T>(path, options, callOptions, true);
      // The refresh cookie itself is gone/expired (7-day ceiling, or the
      // account was deactivated) — nothing left to silently recover from.
      // Clear state and send them to log in again instead of leaving the
      // page showing a raw "Unauthorized" error on every subsequent action.
      auth.clearSession();
      await navigateTo('/staff/login');
    }

    const body = await response.json().catch(() => null);

    if (!response.ok) {
      const msg = userMessage(response.status, typeof body?.message === 'string' ? body.message : null);
      const sessionExpired = response.status === 401 && wasStaffAuthed;
      const underMaintenance = response.status === 503 && body?.code === 'MAINTENANCE_MODE';
      if (underMaintenance) useMaintenanceMode().enter();
      else if (!sessionExpired) notify(false, options.method, callOptions, msg);
      throw new Error(msg);
    }

    notify(true, options.method, callOptions);
    return body?.data as T;
  }

  // Separate from `request` because a multipart body must NOT get the
  // `Content-Type: application/json` header `request` always sets — the
  // browser needs to set its own multipart boundary.
  async function upload<T>(path: string, file: File, fieldName = 'image', callOptions: ApiCallOptions = {}): Promise<T> {
    const headers = new Headers();
    if (auth.accessToken.value) headers.set('Authorization', `Bearer ${auth.accessToken.value}`);

    const formData = new FormData();
    formData.append(fieldName, file);

    let response: Response;
    try {
      response = await fetch(`${apiBase}${path}`, {
        method: 'POST',
        headers,
        body: formData,
        credentials: 'include',
      });
    } catch {
      const msg = userMessage(null, null);
      notify(false, 'POST', callOptions, msg);
      throw new Error(msg);
    }

    const body = await response.json().catch(() => null);
    if (!response.ok) {
      const msg = userMessage(response.status, typeof body?.message === 'string' ? body.message : null);
      const underMaintenance = response.status === 503 && body?.code === 'MAINTENANCE_MODE';
      if (underMaintenance) useMaintenanceMode().enter();
      else notify(false, 'POST', callOptions, msg);
      throw new Error(msg);
    }
    notify(true, 'POST', callOptions);
    return body?.data as T;
  }

  return {
    get: <T>(path: string) => request<T>(path),
    post: <T>(path: string, data?: unknown, callOptions?: ApiCallOptions) =>
      request<T>(path, { method: 'POST', body: JSON.stringify(data) }, callOptions),
    patch: <T>(path: string, data?: unknown, callOptions?: ApiCallOptions) =>
      request<T>(path, { method: 'PATCH', body: JSON.stringify(data) }, callOptions),
    put: <T>(path: string, data?: unknown, callOptions?: ApiCallOptions) =>
      request<T>(path, { method: 'PUT', body: JSON.stringify(data) }, callOptions),
    del: <T>(path: string, callOptions?: ApiCallOptions) => request<T>(path, { method: 'DELETE' }, callOptions),
    upload,
  };
}
