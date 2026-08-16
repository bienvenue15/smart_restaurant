<script setup lang="ts">
import type { OrderItemLine, StaffOrder } from '~/types/order';

definePageMeta({ middleware: 'staff-auth' });

const api = useApi();
const orders = ref<StaffOrder[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const busyItemId = ref<string | null>(null);

// Backend scopes this list to CONFIRMED/PREPARING/READY orders for the
// KITCHEN role automatically (order.service.ts::listOrders) — kitchen
// never sees raw PENDING orders, matching the legacy rule that orders
// only enter the prep pipeline once a waiter confirms them
// (docs/CURRENT_SYSTEM_AUDIT.md §4).
async function loadOrders() {
  loading.value = true;
  error.value = null;
  try {
    orders.value = await api.get<StaffOrder[]>('/staff/orders');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load kitchen queue';
  } finally {
    loading.value = false;
  }
}

const NEXT_ITEM_STATUS: Record<string, string | null> = {
  PENDING: 'PREPARING',
  PREPARING: 'READY',
  READY: null,
  SERVED: null,
};

async function advanceItem(item: OrderItemLine) {
  const next = NEXT_ITEM_STATUS[item.status];
  if (!next) return;
  busyItemId.value = item.id;
  try {
    await api.patch(`/staff/orders/items/${item.id}/status`, { status: next });
    await loadOrders();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to update item';
  } finally {
    busyItemId.value = null;
  }
}

function elapsedMinutes(createdAt: string): number {
  return Math.floor((Date.now() - new Date(createdAt).getTime()) / 60000);
}

onMounted(loadOrders);
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-gray-900">Kitchen</h1>
      <BaseButton variant="secondary" :disabled="loading" @click="loadOrders">Refresh</BaseButton>
    </div>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="orders.length === 0" class="text-sm text-gray-500">No orders in the prep queue.</p>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="order in orders" :key="order.id" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="mb-2 flex items-center justify-between">
          <p class="font-medium text-gray-900">Table {{ order.table.tableNumber }}</p>
          <span class="text-xs text-gray-500">{{ elapsedMinutes(order.createdAt) }} min ago</span>
        </div>

        <ul class="space-y-2">
          <li v-for="item in order.items" :key="item.id" class="flex items-center justify-between gap-2 rounded border border-gray-100 p-2">
            <div>
              <p class="text-sm font-medium text-gray-900">{{ item.quantity }}× {{ item.menuItem.name }}</p>
              <p v-if="item.specialRequest" class="text-xs text-gray-500">{{ item.specialRequest }}</p>
              <p class="text-xs uppercase text-gray-400">{{ item.status }}</p>
            </div>
            <BaseButton
              v-if="NEXT_ITEM_STATUS[item.status]"
              :disabled="busyItemId === item.id"
              @click="advanceItem(item)"
            >
              {{ NEXT_ITEM_STATUS[item.status] }}
            </BaseButton>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
