import type { StaffNotification } from '~/types/notification';

const POLL_INTERVAL_MS = 45000;

/**
 * Staff notification list. SSE pushes an immediate refresh; a slow poll
 * remains as a fallback if the event stream drops.
 */
// Notification types that should interrupt with a toast + chime the moment
// they arrive, not just sit in the bell dropdown until someone clicks it.
const INTERRUPTING_TYPES = new Set(['order_received', 'new_order', 'order_assignment', 'approval_resolved']);

export function useNotifications() {
  const notifications = useState<StaffNotification[]>('staff:notifications', () => []);
  // Tracks which ids we've already fetched at least once, so a one-shot
  // chime/toast only fires for notifications that arrive *during* this
  // session — not for a backlog of unread notifications already sitting
  // there on load.
  const seenIds = useState<Set<string> | null>('staff:notifications:seenIds', () => null);
  const sound = useAlertSound();
  const toast = useToast();
  const { render: renderNotification } = useNotificationText();
  let timer: ReturnType<typeof setInterval> | null = null;
  const stream = useEventStream('/staff/events', () => {
    void refresh();
  });

  const unreadCount = computed(() => notifications.value.filter((n) => !n.isRead).length);

  async function refresh() {
    const auth = useAuthStore();
    if (!auth.accessToken.value) return;
    const api = useApi();
    try {
      const next = await api.get<StaffNotification[]>('/staff/notifications');
      if (seenIds.value) {
        let chimed = false;
        for (const n of next) {
          if (!n.isRead && INTERRUPTING_TYPES.has(n.type) && !seenIds.value.has(n.id)) {
            if (!chimed) {
              sound.playOrderChime();
              chimed = true;
            }
            const rendered = renderNotification(n);
            toast.show(rendered.title, rendered.message);
          }
        }
      }
      seenIds.value = new Set(next.map((n) => n.id));
      notifications.value = next;
    } catch {
      // Skip a failed poll tick — the next interval retries.
    }
  }

  async function markRead(id: string) {
    const api = useApi();
    await api.post(`/staff/notifications/${id}/read`, undefined, { silent: true });
    const n = notifications.value.find((x) => x.id === id);
    if (n) n.isRead = true;
  }

  async function markAllRead() {
    const api = useApi();
    await api.post('/staff/notifications/read-all', undefined, { silent: true });
    notifications.value.forEach((n) => (n.isRead = true));
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

  return { notifications, unreadCount, refresh, markRead, markAllRead, start, stop };
}
