<script setup lang="ts">
import type { OrderItemLine, StaffOrder } from '~/types/order';
import type { ShiftStatus } from '~/types/staff';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['view_kitchen'] });

const { t } = useI18n();
const api = useApi();
const { playOrderChime } = useAlertSound();
const orders = ref<StaffOrder[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const busyItemId = ref<string | null>(null);
const busyTicketId = ref<string | null>(null);
const shiftStatus = ref<ShiftStatus | null>(null);
const shiftBusy = ref(false);

const BEVERAGE_CATEGORY_NAMES = new Set(['beverages', 'beverage', 'drinks', 'drink', 'beer', 'wine', 'cocktails', 'soft drinks']);
function isBeverageItem(item: OrderItemLine): boolean {
  return BEVERAGE_CATEGORY_NAMES.has((item.menuItem.category?.name ?? '').trim().toLowerCase());
}

function foodItems(order: StaffOrder): OrderItemLine[] {
  return order.items.filter((item) => !isBeverageItem(item));
}

type KitchenColumn = 'incoming' | 'cooking' | 'ready';

function ticketColumn(order: StaffOrder): KitchenColumn | null {
  const food = foodItems(order);
  if (food.length === 0) return null;
  if (food.every((item) => item.status === 'READY' || item.status === 'SERVED')) return 'ready';
  if (food.some((item) => item.status === 'PREPARING' || item.status === 'READY' || item.status === 'SERVED')) return 'cooking';
  return 'incoming';
}

const columns = computed(() => {
  const incoming: StaffOrder[] = [];
  const cooking: StaffOrder[] = [];
  const ready: StaffOrder[] = [];
  for (const order of orders.value) {
    const col = ticketColumn(order);
    if (col === 'incoming') incoming.push(order);
    else if (col === 'cooking') cooking.push(order);
    else if (col === 'ready') ready.push(order);
  }
  return { incoming, cooking, ready };
});

const boardEmpty = computed(
  () => columns.value.incoming.length + columns.value.cooking.length + columns.value.ready.length === 0,
);

async function loadShift() {
  try {
    shiftStatus.value = await api.get<ShiftStatus>('/staff/users/me/shift-status');
  } catch {
    shiftStatus.value = null;
  }
}

async function toggleShift() {
  shiftBusy.value = true;
  try {
    if (shiftStatus.value?.onShift) {
      await api.post('/staff/users/me/clock-out');
    } else {
      await api.post('/staff/users/me/clock-in');
    }
    await loadShift();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.dashboard.shiftUpdateFailed');
  } finally {
    shiftBusy.value = false;
  }
}

let primed = false;
const knownIds = new Set<string>();

async function loadOrders(opts?: { silent?: boolean }) {
  if (!opts?.silent) loading.value = true;
  error.value = null;
  try {
    const next = await api.get<StaffOrder[]>('/staff/orders?view=kitchen');
    if (primed) {
      const isNew = next.some((order) => !knownIds.has(order.id));
      if (isNew) playOrderChime();
    }
    knownIds.clear();
    for (const order of next) knownIds.add(order.id);
    primed = true;
    orders.value = next;
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.kitchen.loadFailed');
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

async function setItemStatus(item: OrderItemLine, status: string) {
  busyItemId.value = item.id;
  try {
    await api.patch(`/staff/orders/items/${item.id}/status`, { status });
    await loadOrders({ silent: true });
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.kitchen.itemUpdateFailed');
  } finally {
    busyItemId.value = null;
  }
}

async function advanceItem(item: OrderItemLine) {
  const next = NEXT_ITEM_STATUS[item.status];
  if (!next) return;
  await setItemStatus(item, next);
}

async function startTicket(order: StaffOrder) {
  const pending = foodItems(order).filter((item) => item.status === 'PENDING');
  if (pending.length === 0) return;
  busyTicketId.value = order.id;
  try {
    await Promise.all(pending.map((item) => api.patch(`/staff/orders/items/${item.id}/status`, { status: 'PREPARING' })));
    await loadOrders({ silent: true });
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.kitchen.itemUpdateFailed');
  } finally {
    busyTicketId.value = null;
  }
}

async function markTicketReady(order: StaffOrder) {
  const cooking = foodItems(order).filter((item) => item.status === 'PREPARING');
  if (cooking.length === 0) return;
  busyTicketId.value = order.id;
  try {
    await Promise.all(cooking.map((item) => api.patch(`/staff/orders/items/${item.id}/status`, { status: 'READY' })));
    await loadOrders({ silent: true });
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.kitchen.itemUpdateFailed');
  } finally {
    busyTicketId.value = null;
  }
}

function elapsedMinutes(iso: string): number {
  return Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
}

function statusLabel(status: string | null): string {
  if (!status) return '';
  return t(`staff.kitchen.status.${status.toLowerCase()}`);
}

const DEFAULT_PREP_MINUTES = 15;

function isDelayed(order: StaffOrder): boolean {
  if (!order.confirmedAt) return false;
  const food = foodItems(order);
  const estimatedMinutes =
    food.length > 0 ? Math.max(...food.map((i) => i.menuItem.preparationTime ?? DEFAULT_PREP_MINUTES)) : DEFAULT_PREP_MINUTES;
  return elapsedMinutes(order.confirmedAt) > estimatedMinutes;
}

const onShift = computed(() => shiftStatus.value?.onShift === true);

const SAFETY_POLL_MS = 60000;
let refreshTimer: ReturnType<typeof setInterval> | null = null;
const liveRefresh = useLiveRefresh('/staff/events', () => loadOrders({ silent: true }), ['order_status', 'new_order']);
onMounted(() => {
  void loadShift();
  void loadOrders();
  liveRefresh.start();
  refreshTimer = setInterval(() => void loadOrders({ silent: true }), SAFETY_POLL_MS);
});
onScopeDispose(() => {
  if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.kitchen')" :subtitle="$t('staff.pages.kitchenSub')">
      <template #actions>
        <span
          class="staff-chip"
          :class="onShift ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'"
        >
          {{ onShift ? $t('staff.dashboard.onShift') : $t('staff.dashboard.offShift') }}
        </span>
        <BaseButton
          :variant="onShift ? 'danger' : 'primary'"
          :disabled="shiftBusy"
          @click="toggleShift"
        >
          {{ onShift ? $t('staff.dashboard.clockOut') : $t('staff.dashboard.clockIn') }}
        </BaseButton>
        <BaseButton variant="secondary" :disabled="loading" @click="loadOrders()">{{ $t('common.refresh') }}</BaseButton>
      </template>
    </StaffPageHeader>

    <p v-if="error" class="staff-error">{{ error }}</p>

    <div
      v-if="!onShift"
      class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
    >
      {{ $t('staff.kitchen.clockInToPrep') }}
    </div>

    <div v-if="loading" class="staff-empty">{{ $t('staff.kitchen.loading') }}</div>
    <div v-else-if="boardEmpty" class="staff-empty">{{ $t('staff.kitchen.empty') }}</div>

    <div v-else class="grid gap-4 lg:grid-cols-3">
      <section
        v-for="col in [
          { key: 'incoming' as const, items: columns.incoming },
          { key: 'cooking' as const, items: columns.cooking },
          { key: 'ready' as const, items: columns.ready },
        ]"
        :key="col.key"
        class="min-w-0"
      >
        <header class="mb-3 flex items-center justify-between">
          <h2 class="font-display text-sm font-bold uppercase tracking-wide text-ink-muted">
            {{ $t(`staff.kitchen.col.${col.key}`) }}
          </h2>
          <span class="staff-chip bg-gray-100 text-gray-600">{{ col.items.length }}</span>
        </header>

        <p v-if="col.items.length === 0" class="staff-empty py-6">{{ $t('staff.kitchen.allCaughtUp') }}</p>

        <div class="space-y-3">
          <article
            v-for="order in col.items"
            :key="order.id"
            class="staff-card"
            :class="isDelayed(order) ? 'border-red-300 ring-2 ring-red-200' : ''"
          >
            <div class="mb-3 flex items-start justify-between gap-2">
              <div>
                <p class="font-display text-lg font-bold text-ink">{{ $t('staff.kitchen.tableLabel', { number: order.table.tableNumber }) }}</p>
                <p class="text-xs text-ink-muted">{{ order.orderNumber }}</p>
              </div>
              <span
                class="staff-chip"
                :class="isDelayed(order) ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600'"
              >
                {{ isDelayed(order)
                  ? $t('staff.kitchen.delayedMinutes', { minutes: elapsedMinutes(order.confirmedAt ?? order.createdAt) })
                  : $t('staff.kitchen.minutes', { minutes: elapsedMinutes(order.confirmedAt ?? order.createdAt) }) }}
              </span>
            </div>

            <p v-if="order.specialInstructions" class="mb-3 rounded-lg bg-amber-50 px-2.5 py-1.5 text-sm font-semibold text-amber-900">
              {{ order.specialInstructions }}
            </p>

            <ul class="space-y-2">
              <li
                v-for="item in foodItems(order)"
                :key="item.id"
                class="flex items-center justify-between gap-2 rounded-xl border border-gray-100 bg-surface/60 p-3"
              >
                <div class="min-w-0">
                  <p class="text-base font-bold text-ink">{{ item.quantity }}× {{ item.menuItem.name }}</p>
                  <p v-if="item.specialRequest" class="text-xs font-semibold text-clay">{{ item.specialRequest }}</p>
                  <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ statusLabel(item.status) }}</p>
                </div>
                <BaseButton
                  v-if="col.key === 'cooking' && NEXT_ITEM_STATUS[item.status]"
                  :disabled="!onShift || busyItemId === item.id || busyTicketId === order.id"
                  @click="advanceItem(item)"
                >
                  {{ statusLabel(NEXT_ITEM_STATUS[item.status] ?? null) }}
                </BaseButton>
              </li>
            </ul>

            <div v-if="col.key === 'incoming'" class="mt-3">
              <BaseButton
                class="w-full"
                :disabled="!onShift || busyTicketId === order.id"
                @click="startTicket(order)"
              >
                {{ $t('staff.kitchen.startTicket') }}
              </BaseButton>
            </div>
            <div v-else-if="col.key === 'cooking' && foodItems(order).some((i) => i.status === 'PREPARING')" class="mt-3">
              <BaseButton
                variant="secondary"
                class="w-full"
                :disabled="!onShift || busyTicketId === order.id"
                @click="markTicketReady(order)"
              >
                {{ $t('staff.kitchen.markAllReady') }}
              </BaseButton>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>
