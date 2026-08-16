<script setup lang="ts">
import type { StaffOrder } from '~/types/order';

definePageMeta({ middleware: 'staff-auth', layout: 'staff' });

const api = useApi();
const orders = ref<StaffOrder[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const busyOrderId = ref<string | null>(null);

async function loadOrders() {
  loading.value = true;
  error.value = null;
  try {
    orders.value = await api.get<StaffOrder[]>('/staff/orders');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load orders';
  } finally {
    loading.value = false;
  }
}

// Mirrors the waiter-facing transition table in
// backend/src/modules/orders/order.state.ts — only these transitions are
// ever accepted server-side, so the UI only offers what will succeed.
const NEXT_STATUS: Record<string, string | null> = {
  PENDING: 'CONFIRMED',
  CONFIRMED: null,
  PREPARING: null,
  READY: 'COMPLETED',
  SERVED: 'COMPLETED',
  COMPLETED: null,
  CANCELLED: null,
};

async function advanceStatus(order: StaffOrder) {
  const next = NEXT_STATUS[order.status];
  if (!next) return;
  busyOrderId.value = order.id;
  try {
    await api.patch(`/staff/orders/${order.id}/status`, { status: next });
    await loadOrders();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to update order';
  } finally {
    busyOrderId.value = null;
  }
}

const statusColor: Record<string, string> = {
  PENDING: 'bg-gray-100 text-gray-700',
  CONFIRMED: 'bg-blue-100 text-blue-700',
  PREPARING: 'bg-amber-100 text-amber-700',
  READY: 'bg-green-100 text-green-700',
  SERVED: 'bg-indigo-100 text-indigo-700',
  COMPLETED: 'bg-gray-100 text-gray-500',
  CANCELLED: 'bg-red-100 text-red-700',
};

onMounted(loadOrders);
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-gray-900">Orders</h1>
      <BaseButton variant="secondary" :disabled="loading" @click="loadOrders">Refresh</BaseButton>
    </div>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="orders.length === 0" class="text-sm text-gray-500">No orders yet.</p>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="order in orders" :key="order.id" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="mb-2 flex items-center justify-between">
          <p class="font-medium text-gray-900">{{ order.orderNumber }}</p>
          <span class="rounded px-2 py-0.5 text-xs font-medium" :class="statusColor[order.status]">{{ order.status }}</span>
        </div>
        <p class="text-sm text-gray-500">Table {{ order.table.tableNumber }} · {{ order.paymentStatus }}</p>

        <ul class="mt-3 space-y-1 text-sm text-gray-700">
          <li v-for="item in order.items" :key="item.id">{{ item.quantity }}× {{ item.menuItem.name }}</li>
        </ul>

        <div class="mt-3 flex items-center justify-between">
          <p class="text-sm font-semibold text-gray-900">{{ Number(order.totalAmount).toLocaleString() }}</p>
          <BaseButton
            v-if="NEXT_STATUS[order.status]"
            :disabled="busyOrderId === order.id"
            @click="advanceStatus(order)"
          >
            Mark {{ NEXT_STATUS[order.status] }}
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
