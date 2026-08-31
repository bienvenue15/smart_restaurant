/**
 * Client-only PNG data URL for a QR code. Generation needs `window` for the
 * menu origin and the `qrcode` canvas/PNG path, so this is a no-op on SSR.
 */
import type { MaybeRefOrGetter } from 'vue';

export function useQrDataUrl(value: MaybeRefOrGetter<string>, size: MaybeRefOrGetter<number> = 200) {
  const dataUrl = ref('');

  async function render() {
    if (!import.meta.client) return;
    const text = toValue(value);
    const pixels = toValue(size);
    if (!text) {
      dataUrl.value = '';
      return;
    }
    const QRCode = (await import('qrcode')).default;
    dataUrl.value = await QRCode.toDataURL(text, {
      width: pixels,
      margin: 1,
      errorCorrectionLevel: 'M',
      color: { dark: '#221a10', light: '#ffffff' },
    });
  }

  watch([() => toValue(value), () => toValue(size)], () => void render(), { immediate: true });

  return dataUrl;
}
