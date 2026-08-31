/**
 * Subscribes a page's reload function to a live SSE channel, optionally
 * filtered to specific event types (e.g. only 'table_status'/'order_status'
 * on the tables page, ignoring unrelated staff-channel chatter).
 */
export function useLiveRefresh(path: string, refresh: () => void | Promise<void>, eventTypes?: string[]) {
  const stream = useEventStream(path, (event) => {
    if (eventTypes && !eventTypes.includes(event.type)) return;
    void refresh();
  });

  function start() {
    stream.start();
  }

  function stop() {
    stream.stop();
  }

  onScopeDispose(stop);

  return { start, stop };
}
