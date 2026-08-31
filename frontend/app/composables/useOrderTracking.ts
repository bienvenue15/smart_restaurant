import type { OrderSummary } from '~/types/menu';

const ACTIVE_STATUSES = new Set(['PENDING', 'CONFIRMED', 'PREPARING', 'READY', 'SERVED']);
const POLL_INTERVAL_MS = 20000;

/**
 * Customer order tracking: SSE for live kitchen status, with polling fallback.
 */
export function useOrderTracking(tableId: Ref<string | null>) {
  const orders = useState<OrderSummary[]>('customer:orders', () => []);
  const loading = ref(false);
  let timer: ReturnType<typeof setInterval> | null = null;
  const stream = useEventStream('/customer/events', () => {
    void refresh();
  });

  const hasActiveOrder = computed(() => orders.value.some((o) => ACTIVE_STATUSES.has(o.status)));

  async function refresh() {
    if (!tableId.value) return;
    const api = useApi();
    try {
      orders.value = await api.get<OrderSummary[]>('/customer/orders');
    } catch {
      // Silently skip a failed poll tick — the next interval retries.
    }
  }

  async function cancelOrder(orderId: string) {
    const api = useApi();
    await api.post(`/customer/orders/${orderId}/cancel`, undefined, { successMessage: false });
    await refresh();
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

  return { orders, loading, hasActiveOrder, refresh, cancelOrder, start, stop };
}
