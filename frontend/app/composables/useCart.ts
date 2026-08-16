import type { CartLine } from '~/types/menu';

/**
 * Cart persistence — the legacy app kept the cart in a plain in-memory JS
 * array with no persistence at all, so a page refresh silently lost it
 * (docs/CURRENT_SYSTEM_AUDIT.md §4.1). This is an explicit, documented
 * improvement over legacy behavior, not a parity requirement — it does not
 * change the underlying "session pinned to one table via a signed token"
 * security model at all, it just survives a reload. Scoped per tableId so
 * switching tables (a new QR scan) never leaks a stale cart across tables.
 */
export function useCart(tableId: Ref<string | null>) {
  const cart = useState<CartLine[]>('cart:lines', () => []);

  function storageKey(id: string) {
    return `sr_cart_${id}`;
  }

  function load() {
    if (!import.meta.client || !tableId.value) return;
    const raw = localStorage.getItem(storageKey(tableId.value));
    cart.value = raw ? (JSON.parse(raw) as CartLine[]) : [];
  }

  function persist() {
    if (!import.meta.client || !tableId.value) return;
    localStorage.setItem(storageKey(tableId.value), JSON.stringify(cart.value));
  }

  function addItem(item: { id: string; name: string; price: number }) {
    const existing = cart.value.find((line) => line.menuItemId === item.id);
    if (existing) {
      existing.quantity += 1;
    } else {
      cart.value.push({ menuItemId: item.id, name: item.name, price: item.price, quantity: 1 });
    }
    persist();
  }

  function updateQuantity(menuItemId: string, quantity: number) {
    if (quantity <= 0) {
      cart.value = cart.value.filter((line) => line.menuItemId !== menuItemId);
    } else {
      const line = cart.value.find((l) => l.menuItemId === menuItemId);
      if (line) line.quantity = quantity;
    }
    persist();
  }

  function clear() {
    cart.value = [];
    persist();
  }

  const total = computed(() => cart.value.reduce((sum, line) => sum + line.price * line.quantity, 0));
  const itemCount = computed(() => cart.value.reduce((sum, line) => sum + line.quantity, 0));

  watch(tableId, load, { immediate: true });

  return { cart, addItem, updateQuantity, clear, total, itemCount };
}
