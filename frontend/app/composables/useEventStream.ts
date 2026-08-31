export interface StreamEvent {
  type: string;
  payload: unknown;
}

interface SharedStream {
  controller: AbortController | null;
  listeners: Set<(event: StreamEvent) => void>;
  reconnectTimer: ReturnType<typeof setTimeout> | null;
}

// One real connection per SSE path, shared across every composable/component
// that subscribes to it (ref-counted via the listener set) — otherwise a
// single page pulling in several live-updating composables (bell, call
// alerts, order tracking, a live list, ...) would each open their own
// long-lived fetch stream to the same endpoint.
const sharedStreams = new Map<string, SharedStream>();

function parseFrame(rawFrame: string): StreamEvent | null {
  if (!rawFrame || rawFrame.startsWith(':')) return null; // heartbeat/comment line
  let type = 'message';
  let data = '';
  for (const line of rawFrame.split('\n')) {
    if (line.startsWith('event:')) type = line.slice(6).trim();
    else if (line.startsWith('data:')) data += line.slice(5).trim();
  }
  if (type === 'ready') return null;
  let payload: unknown = null;
  if (data) {
    try {
      payload = JSON.parse(data);
    } catch {
      // Malformed payload — still deliver the event by type, just without data.
    }
  }
  return { type, payload };
}

async function pump(path: string, entry: SharedStream): Promise<void> {
  const runtime = useRuntimeConfig();
  const auth = useAuthStore();
  entry.controller = new AbortController();

  try {
    const headers = new Headers();
    if (auth.accessToken.value) headers.set('Authorization', `Bearer ${auth.accessToken.value}`);
    const response = await fetch(`${runtime.public.apiBaseUrl}${path}`, {
      headers,
      credentials: 'include',
      signal: entry.controller.signal,
    });
    if (!response.ok || !response.body) throw new Error(`SSE ${response.status}`);

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';
    while (entry.listeners.size > 0) {
      const { done, value } = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, { stream: true });
      let boundary: number;
      while ((boundary = buffer.indexOf('\n\n')) !== -1) {
        const frame = buffer.slice(0, boundary);
        buffer = buffer.slice(boundary + 2);
        const event = parseFrame(frame);
        if (event) entry.listeners.forEach((fn) => fn(event));
      }
    }
  } catch {
    // Drop through to reconnect — polling composables remain the safety net.
  }

  if (entry.listeners.size > 0) {
    entry.reconnectTimer = setTimeout(() => void pump(path, entry), 8000);
  }
}

/**
 * Staff/customer SSE with polling fallback. fetch() is used instead of
 * EventSource so the in-memory Bearer token can be sent (EventSource cannot
 * set Authorization headers). The connection itself is shared per path (see
 * above); each call to this composable just adds/removes a listener.
 */
export function useEventStream(path: string, onEvent: (event: StreamEvent) => void) {
  function start() {
    if (!import.meta.client) return;
    let entry = sharedStreams.get(path);
    if (!entry) {
      entry = { controller: null, listeners: new Set(), reconnectTimer: null };
      sharedStreams.set(path, entry);
    }
    const wasEmpty = entry.listeners.size === 0;
    entry.listeners.add(onEvent);
    if (wasEmpty) void pump(path, entry);
  }

  function stop() {
    const entry = sharedStreams.get(path);
    if (!entry) return;
    entry.listeners.delete(onEvent);
    if (entry.listeners.size === 0) {
      entry.controller?.abort();
      entry.controller = null;
      if (entry.reconnectTimer) clearTimeout(entry.reconnectTimer);
      entry.reconnectTimer = null;
      sharedStreams.delete(path);
    }
  }

  onScopeDispose(stop);

  return { start, stop };
}
