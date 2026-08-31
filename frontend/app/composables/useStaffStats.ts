import type { DashboardStats } from '~/types/staff';

const POLL_INTERVAL_MS = 45000;

/**
 * Shared dashboard-stats store — same `useState` + SSE + poll-fallback
 * pattern as useNotifications.ts. Lives here (not just inside
 * staff/dashboard.vue) so the sidebar nav badges (staff.vue layout) can
 * read the same "needs action" counts without a second, duplicate fetch
 * loop — `start()` is idempotent-safe to call from multiple mounted
 * components since `useState` is a single shared ref app-wide.
 */
export function useStaffStats() {
  const stats = useState<DashboardStats | null>('staff:dashboardStats', () => null);
  let timer: ReturnType<typeof setInterval> | null = null;
  const stream = useEventStream('/staff/events', () => {
    void refresh();
  });

  async function refresh() {
    const auth = useAuthStore();
    if (!auth.accessToken.value) return;
    const api = useApi();
    try {
      stats.value = await api.get<DashboardStats>('/staff/reports/dashboard');
    } catch {
      // Skip a failed poll tick — the next interval retries.
    }
  }

  function start() {
    stop();
    void refresh();
    timer = setInterval(refresh, POLL_INTERVAL_MS);
    stream.start();
  }

  function stop() {
    if (timer) clearInterval(timer);
    timer = null;
    stream.stop();
  }

  onScopeDispose(stop);

  return { stats, refresh, start, stop };
}
