<script setup lang="ts">
import type { MenuCategory, MenuItem, OrderSummary } from '~/types/menu';

const route = useRoute();
const qr = route.params.qr as string;

const api = useApi();
const session = useCustomerSession();
const tableId = computed(() => session.table.value?.id ?? null);
const { cart, addItem, updateQuantity, clear, total } = useCart(tableId);

const loading = ref(true);
const error = ref<string | null>(null);
const categories = ref<MenuCategory[]>([]);
const cartOpen = ref(false);
const placing = ref(false);
const placedOrder = ref<OrderSummary | null>(null);

async function init() {
  loading.value = true;
  error.value = null;
  try {
    await session.scan(qr);
    categories.value = await api.get<MenuCategory[]>('/customer/menu');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Unable to load this table';
  } finally {
    loading.value = false;
  }
}

function handleAdd(item: MenuItem) {
  addItem({ id: item.id, name: item.name, price: Number(item.price) });
  cartOpen.value = true;
}

async function placeOrder() {
  if (!session.table.value) return;
  placing.value = true;
  error.value = null;
  try {
    // Deliberately does NOT send `price` — the backend always looks up the
    // authoritative price server-side (docs/SECURITY_AUDIT.md #2). Sending
    // a price here would simply be ignored.
    const order = await api.post<OrderSummary>('/customer/orders', {
      tableId: session.table.value.id,
      items: cart.value.map((line) => ({ menuItemId: line.menuItemId, quantity: line.quantity })),
    });
    placedOrder.value = order;
    clear();
    cartOpen.value = false;
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Could not place order';
  } finally {
    placing.value = false;
  }
}

onMounted(init);
</script>

<template>
  <div class="mx-auto max-w-2xl p-4 pb-24">
    <div v-if="loading" class="py-16 text-center text-gray-500">Loading menu…</div>

    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
      {{ error }}
    </div>

    <template v-else>
      <header class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ session.restaurant.value?.name }}</h1>
        <p class="text-sm text-gray-500">Table {{ session.table.value?.tableNumber }}</p>
      </header>

      <div v-if="placedOrder" class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
        Order {{ placedOrder.orderNumber }} placed — status: {{ placedOrder.status }}
      </div>

      <section v-for="category in categories" :key="category.id" class="mb-8">
        <h2 class="mb-3 text-lg font-semibold text-gray-900">{{ category.name }}</h2>
        <div class="space-y-3">
          <MenuItemCard v-for="item in category.items" :key="item.id" :item="item" @add="handleAdd" />
        </div>
      </section>
    </template>

    <button
      v-if="!loading && !error"
      class="fixed bottom-4 right-4 rounded-full bg-blue-600 px-5 py-3 text-sm font-medium text-white shadow-lg"
      @click="cartOpen = true"
    >
      Cart ({{ cart.length }})
    </button>

    <CartDrawer
      :open="cartOpen"
      :cart="cart"
      :total="total"
      :placing="placing"
      @close="cartOpen = false"
      @update-quantity="updateQuantity"
      @place-order="placeOrder"
    />
  </div>
</template>
