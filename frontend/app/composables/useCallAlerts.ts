const POLL_INTERVAL_MS = 15000;

/**
 * Rings continuously while any waiter call is still PENDING, and stops the
 * instant it's accepted/completed elsewhere — tied to actual call status
 * (not notification read-state), since "picked up" means someone claimed
 * the call, not that a bell entry got dismissed.
 */
export function useCallAlerts() {
  const pendingCount = useState<number>('staff:pendingCallCount', () => 0);
  const sound = useAlertSound();
  let timer: ReturnType<typeof setInterval> | null = null;
  const stream = useEventStream('/staff/events', () => {
    void refresh();
  });

  async function refresh() {
    const auth = useAuthStore();
    if (!auth.accessToken.value) return;
    const api = useApi();
    try {
      const calls = await api.get<unknown[]>('/staff/waiter-calls?status=PENDING');
      pendingCount.value = calls.length;
    } catch {
      // Role without table-view permission, or a dropped poll tick — either
      // way there's nothing to ring for right now.
      pendingCount.value = 0;
    }
  }

  watch(
    pendingCount,
    (count) => {
      if (count > 0) sound.startCallLoop();
      else sound.stopCallLoop();
    },
    { immediate: true },
  );

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
    sound.stopCallLoop();
  }

  onScopeDispose(stop);

  return { pendingCount, start, stop };
}
