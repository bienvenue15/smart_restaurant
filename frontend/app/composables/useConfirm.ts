export interface ConfirmOptions {
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
  danger?: boolean;
}

export interface ConfirmDialogState {
  open: boolean;
  title: string;
  message: string;
  confirmLabel: string;
  cancelLabel: string;
  danger: boolean;
}

let resolveConfirm: ((value: boolean) => void) | null = null;

/**
 * Styled confirm dialog matching BaseModal — replaces window.confirm so
 * logout and other destructive prompts look like the rest of the app.
 */
export function useConfirm() {
  const dialog = useState<ConfirmDialogState>('app:confirm', () => ({
    open: false,
    title: '',
    message: '',
    confirmLabel: '',
    cancelLabel: '',
    danger: false,
  }));

  function confirm(options: ConfirmOptions): Promise<boolean> {
    if (resolveConfirm) resolveConfirm(false);
    dialog.value = {
      open: true,
      title: options.title,
      message: options.message,
      confirmLabel: options.confirmLabel ?? '',
      cancelLabel: options.cancelLabel ?? '',
      danger: options.danger ?? false,
    };
    return new Promise((resolve) => {
      resolveConfirm = resolve;
    });
  }

  function answer(value: boolean) {
    dialog.value = { ...dialog.value, open: false };
    const resolve = resolveConfirm;
    resolveConfirm = null;
    resolve?.(value);
  }

  return { dialog, confirm, answer };
}
