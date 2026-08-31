export type ToastKind = 'success' | 'error' | 'info';

export interface Toast {
  id: string;
  kind: ToastKind;
  title: string;
  message: string;
}

const TOAST_DURATION_MS: Record<ToastKind, number> = {
  success: 4000,
  error: 7000,
  info: 6000,
};

/**
 * App-wide popup stack. Success/error toasts tell the user whether the
 * action they just took worked; `show` is for interrupting notices
 * (new order, table assignment) that are not a direct click result.
 */
export function useToast() {
  const toasts = useState<Toast[]>('app:toasts', () => []);

  function dismiss(id: string) {
    toasts.value = toasts.value.filter((item) => item.id !== id);
  }

  function push(kind: ToastKind, title: string, message = '') {
    const id = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    toasts.value = [...toasts.value, { id, kind, title, message }];
    setTimeout(() => dismiss(id), TOAST_DURATION_MS[kind]);
  }

  function show(title: string, message: string) {
    push('info', title, message);
  }

  function success(title: string, message = '') {
    push('success', title, message);
  }

  function error(title: string, message = '') {
    push('error', title, message);
  }

  return { toasts, show, success, error, dismiss };
}
