<script setup lang="ts">
import type { OrderSummary } from '~/types/menu';

const props = defineProps<{
  open: boolean;
  orders: OrderSummary[];
}>();

const emit = defineEmits<{
  close: [];
  cancel: [orderId: string];
}>();

const cancelling = ref<string | null>(null);

const STATUS_COLOR: Record<string, string> = {
  PENDING: 'bg-amber-100 text-amber-800',
  CONFIRMED: 'bg-blue-100 text-blue-800',
  PREPARING: 'bg-blue-100 text-blue-800',
  READY: 'bg-green-100 text-green-800',
  SERVED: 'bg-green-100 text-green-800',
  COMPLETED: 'bg-gray-100 text-gray-600',
  CANCELLED: 'bg-red-100 text-red-700',
};

const { t } = useI18n();

function statusLabel(status: string): string {
  return t(`customer.status.${status}`);
}

function canCancel(order: OrderSummary): boolean {
  if (order.status !== 'PENDING') return false;
  return Date.now() - new Date(order.createdAt).getTime() <= 60_000;
}

async function handleCancel(orderId: string) {
  cancelling.value = orderId;
  try {
    emit('cancel', orderId);
  } finally {
    cancelling.value = null;
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="props.open" class="fixed inset-0 z-50 flex justify-end bg-black/30" @click.self="emit('close')">
      <div class="flex h-full w-full max-w-sm flex-col bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 p-4">
          <h2 class="text-lg font-semibold text-gray-900">{{ $t('customer.myOrders') }}</h2>
          <button class="text-gray-400 hover:text-gray-600" @click="emit('close')">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto p-4">
          <p v-if="props.orders.length === 0" class="text-sm text-gray-500">{{ $t('customer.noOrders') }}</p>

          <div v-for="order in props.orders" :key="order.id" class="mb-4 rounded-lg border border-gray-200 p-3">
            <div class="mb-2 flex items-center justify-between">
              <span class="text-sm font-medium text-gray-900">{{ order.orderNumber }}</span>
              <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="STATUS_COLOR[order.status]">
                {{ statusLabel(order.status) }}
              </span>
            </div>

            <ul class="mb-2 space-y-1">
              <li v-for="item in order.items ?? []" :key="item.id" class="flex justify-between text-xs text-gray-600">
                <span>{{ item.quantity }}× {{ item.menuItem.name }}</span>
                <span>{{ Number(item.subtotal).toLocaleString() }}</span>
              </li>
            </ul>

            <div class="flex items-center justify-between text-sm font-semibold text-gray-900">
              <span>{{ $t('common.total') }}</span>
              <span>{{ Number(order.totalAmount).toLocaleString() }}</span>
            </div>

            <button
              v-if="canCancel(order)"
              class="mt-2 w-full rounded-md border border-red-300 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 disabled:opacity-50"
              :disabled="cancelling === order.id"
              @click="handleCancel(order.id)"
            >
              {{ cancelling === order.id ? $t('customer.cancelling') : $t('customer.cancelOrder') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
