<script setup lang="ts">
import type { AdjustmentResult } from '~/types/adjustment';
import type { OnShiftWaiter, StaffOrder } from '~/types/order';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['manage_orders', 'accept_payment'] });

const { t } = useI18n();
const api = useApi();
const toast = useToast();
const auth = useAuthStore();
const { can, canAny } = usePermissions();
const orders = ref<StaffOrder[]>([]);
const onShiftWaiters = ref<OnShiftWaiter[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const busyOrderId = ref<string | null>(null);
const assigningOrderId = ref<string | null>(null);
const selectedWaiterId = ref<Record<string, string>>({});
// Pre-filled from ?status=PENDING when arriving via a dashboard tile link.
const route = useRoute();
const statusFilter = ref(typeof route.query.status === 'string' ? route.query.status : '');
const tableFilter = ref('');

const canAssign = computed(() => canAny('approve_actions', 'manage_staff'));

async function loadOrders() {
  loading.value = true;
  error.value = null;
  try {
    const params = statusFilter.value ? `?status=${statusFilter.value}` : '';
    orders.value = await api.get<StaffOrder[]>(`/staff/orders${params}`);
    if (canAssign.value) {
      onShiftWaiters.value = await api.get<OnShiftWaiter[]>('/staff/orders/onshift-waiters');
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.orders.loadFailed');
  } finally {
    loading.value = false;
  }
}

// Table number has no server-side filter — narrowed client-side over the
// already status-filtered set, same as the rest of this page's small lists.
const filteredOrders = computed(() => {
  const table = tableFilter.value.trim().toLowerCase();
  if (!table) return orders.value;
  return orders.value.filter((o) => o.table.tableNumber.toLowerCase().includes(table));
});

async function assignWaiter(order: StaffOrder) {
  const waiterId = selectedWaiterId.value[order.id];
  if (!waiterId) return;
  assigningOrderId.value = order.id;
  try {
    await api.post(`/staff/orders/${order.id}/assign`, { staffId: waiterId });
    await loadOrders();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.orders.assignFailed');
  } finally {
    assigningOrderId.value = null;
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
    error.value = e instanceof Error ? e.message : t('staff.orders.updateFailed');
  } finally {
    busyOrderId.value = null;
  }
}

// Waiter beverage exception, ported from legacy `updateOrderItemStatus`
// (backend/src/modules/orders/order.state.ts::isBeverageCategory): waiters
// may update a beverage item's status directly rather than waiting on the
// kitchen queue, since pouring a drink doesn't need a prep step. Mirrored
// here — not the source of truth, the backend re-enforces this on every
// request — purely so the button only appears where it will actually succeed.
const BEVERAGE_CATEGORY_NAMES = new Set(['beverages', 'beverage', 'drinks', 'drink', 'beer', 'wine', 'cocktails', 'soft drinks']);
function isBeverageItem(item: StaffOrder['items'][number]): boolean {
  return BEVERAGE_CATEGORY_NAMES.has((item.menuItem.category?.name ?? '').trim().toLowerCase());
}

const isWaiter = computed(() => auth.staff.value?.role === 'WAITER');
const canAdvanceOrder = computed(() => can('manage_orders'));
const canRecordPayment = computed(() => can('accept_payment'));
const canDiscount = computed(() => can('update_orders'));
const canRefund = computed(() => can('refund_orders'));
const showKitchenItemStatus = computed(() => can('view_kitchen') || can('modify_order'));
const REFUND_WINDOW_MS = 24 * 60 * 60 * 1000;

function refundStillOpen(order: StaffOrder): boolean {
  if (Number(order.paidAmount) <= 0) return false;
  if (!order.paidAt) return true;
  return Date.now() - new Date(order.paidAt).getTime() <= REFUND_WINDOW_MS;
}

const adjustingOrderId = ref<string | null>(null);
const adjustmentKind = ref<'discount' | 'refund' | null>(null);
const submittingAdjustment = ref<string | null>(null);
const adjustmentNotice = ref<string | null>(null);
const adjustmentDrafts = ref<Record<string, { discountPercent: number | null; refundAmount: number | null; reason: string }>>({});

function openAdjustment(order: StaffOrder, kind: 'discount' | 'refund') {
  adjustingOrderId.value = order.id;
  adjustmentKind.value = kind;
  adjustmentNotice.value = null;
  adjustmentDrafts.value[order.id] = {
    discountPercent: 5,
    refundAmount: Number(order.paidAmount) || null,
    reason: '',
  };
}

function closeAdjustment() {
  adjustingOrderId.value = null;
  adjustmentKind.value = null;
}

async function submitAdjustment(order: StaffOrder) {
  const draft = adjustmentDrafts.value[order.id];
  const kind = adjustmentKind.value;
  if (!draft || !kind) return;
  if (draft.reason.trim().length < 3) {
    error.value = t('staff.orders.adjustmentReasonRequired');
    return;
  }
  submittingAdjustment.value = order.id;
  error.value = null;
  try {
    let result: AdjustmentResult;
    if (kind === 'discount') {
      if (!draft.discountPercent || draft.discountPercent <= 0) return;
      result = await api.post<AdjustmentResult>(`/staff/adjustments/orders/${order.id}/discount`, {
        discountPercent: draft.discountPercent,
        reason: draft.reason.trim(),
      });
    } else {
      if (!draft.refundAmount || draft.refundAmount <= 0) return;
      result = await api.post<AdjustmentResult>(`/staff/adjustments/orders/${order.id}/refund`, {
        amount: draft.refundAmount,
        reason: draft.reason.trim(),
      });
    }
    const label = kind === 'discount' ? t('staff.orders.discount') : t('staff.orders.refund');
    adjustmentNotice.value =
      result.status === 'PENDING'
        ? t('staff.orders.queuedForApproval', { label })
        : t('staff.orders.appliedImmediately', { label });
    closeAdjustment();
    await loadOrders();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.orders.adjustmentSubmitFailed');
  } finally {
    submittingAdjustment.value = null;
  }
}

const payingOrderId = ref<string | null>(null);
const recordingPayment = ref<string | null>(null);
const paymentDrafts = ref<Record<string, { receivedAmount: number | null; paymentMethod: string; paymentReference: string }>>({});

function outstanding(order: StaffOrder): number {
  return Math.max(0, Number(order.totalAmount) - Number(order.paidAmount));
}

function openPaymentForm(order: StaffOrder) {
  payingOrderId.value = order.id;
  paymentDrafts.value[order.id] = { receivedAmount: outstanding(order), paymentMethod: 'CASH', paymentReference: '' };
}

async function submitPayment(order: StaffOrder) {
  const draft = paymentDrafts.value[order.id];
  if (!draft || !draft.receivedAmount || draft.receivedAmount <= 0) return;
  recordingPayment.value = order.id;
  try {
    const amount = Math.min(draft.receivedAmount, outstanding(order));
    await api.post(
      `/staff/orders/${order.id}/payments`,
      {
        amount,
        receivedAmount: draft.receivedAmount,
        paymentMethod: draft.paymentMethod,
        paymentReference: draft.paymentReference || undefined,
      },
      { successMessage: false },
    );
    payingOrderId.value = null;
    const fullyPaid = amount >= outstanding(order) - 0.01;
    toast.success(fullyPaid ? t('staff.orders.paymentRecordedAwaitingFeedback') : t('staff.orders.paymentRecorded'));
    await loadOrders();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.orders.paymentFailed');
  } finally {
    recordingPayment.value = null;
  }
}

const NEXT_ITEM_STATUS: Record<string, string | null> = {
  PENDING: 'PREPARING',
  PREPARING: 'READY',
  READY: 'SERVED',
  SERVED: null,
};

const busyItemId = ref<string | null>(null);
async function advanceItem(item: StaffOrder['items'][number]) {
  const next = NEXT_ITEM_STATUS[item.status];
  if (!next) return;
  busyItemId.value = item.id;
  try {
    await api.patch(`/staff/orders/items/${item.id}/status`, { status: next });
    await loadOrders();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.orders.itemUpdateFailed');
  } finally {
    busyItemId.value = null;
  }
}

const statusColor: Record<string, string> = {
  PENDING: 'bg-gray-100 text-gray-700',
  CONFIRMED: 'bg-sky-100 text-sky-800',
  PREPARING: 'bg-amber-100 text-amber-900',
  READY: 'bg-emerald-100 text-emerald-800',
  SERVED: 'bg-indigo-100 text-indigo-800',
  COMPLETED: 'bg-gray-100 text-gray-500',
  CANCELLED: 'bg-red-100 text-red-700',
};

function orderStatusLabel(status: string): string {
  return t(`staff.orders.status.${status.toLowerCase()}`);
}

function paymentStatusLabel(status: string): string {
  return t(`staff.orders.paymentStatus.${status.toLowerCase()}`);
}

function itemStatusLabel(status: string | null): string {
  if (!status) return '';
  return t(`staff.kitchen.status.${status.toLowerCase()}`);
}

const liveRefresh = useLiveRefresh('/staff/events', loadOrders, ['order_status', 'new_order', 'order_received', 'order_items_added', 'order_assignment']);
onMounted(() => {
  loadOrders();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.orders')" :subtitle="$t('staff.pages.ordersSub')">
      <template #actions>
        <BaseButton variant="secondary" :disabled="loading" @click="loadOrders">{{ $t('common.refresh') }}</BaseButton>
      </template>
    </StaffPageHeader>

    <p v-if="error" class="staff-error">{{ error }}</p>
    <p v-if="adjustmentNotice" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
      {{ adjustmentNotice }}
    </p>
    <div class="mb-4 flex flex-wrap items-center gap-2">
      <select v-model="statusFilter" class="staff-select" @change="loadOrders">
        <option value="">{{ $t('staff.orders.allStatuses') }}</option>
        <option value="PENDING">{{ orderStatusLabel('PENDING') }}</option>
        <option value="CONFIRMED">{{ orderStatusLabel('CONFIRMED') }}</option>
        <option value="PREPARING">{{ orderStatusLabel('PREPARING') }}</option>
        <option value="READY">{{ orderStatusLabel('READY') }}</option>
        <option value="SERVED">{{ orderStatusLabel('SERVED') }}</option>
        <option value="COMPLETED">{{ orderStatusLabel('COMPLETED') }}</option>
        <option value="CANCELLED">{{ orderStatusLabel('CANCELLED') }}</option>
      </select>
      <input v-model="tableFilter" type="text" :placeholder="$t('staff.orders.filterByTable')" class="staff-input !w-40 !py-1.5 text-sm" />
    </div>

    <div v-if="canAssign && onShiftWaiters.length > 0" class="staff-card mb-4">
      <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $t('staff.orders.waiterWorkload') }}</p>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="w in onShiftWaiters" :key="w.id" class="rounded-lg bg-surface px-3 py-2">
          <p class="flex items-center justify-between gap-2 text-sm font-semibold text-ink">
            <span>{{ w.fullName }}</span>
            <span class="staff-chip bg-white text-ink-muted">{{ $t('staff.orders.orderCount', { count: w.activeOrders.length }) }}</span>
          </p>
          <ul v-if="w.activeOrders.length > 0" class="mt-1 space-y-0.5">
            <li v-for="o in w.activeOrders" :key="o.id" class="text-xs text-ink-muted">
              {{
                $t('staff.approvals.currentOrderLine', {
                  table: o.tableNumber,
                  orderNumber: o.orderNumber,
                  status: orderStatusLabel(o.status),
                })
              }}
            </li>
          </ul>
          <p v-else class="mt-1 text-xs text-gray-400">{{ $t('staff.orders.noActiveOrders') }}</p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="staff-empty">{{ $t('staff.orders.loading') }}</div>
    <div v-else-if="filteredOrders.length === 0" class="staff-empty">{{ $t('staff.orders.noOrders') }}</div>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article v-for="order in filteredOrders" :key="order.id" class="staff-card flex flex-col">
        <div class="mb-2 flex items-start justify-between gap-2">
          <div>
            <p class="font-display font-bold text-ink">{{ order.orderNumber }}</p>
            <p class="text-sm text-ink-muted">
              {{ $t('staff.orders.tableLabel', { number: order.table.tableNumber }) }} · {{ paymentStatusLabel(order.paymentStatus) }}
            </p>
            <p class="mt-0.5 text-xs text-gray-400">
              {{ order.createdByStaff ? $t('staff.orders.assignedTo', { name: order.createdByStaff.fullName }) : $t('staff.orders.unassigned') }}
            </p>
          </div>
          <span class="staff-chip" :class="statusColor[order.status]">{{ orderStatusLabel(order.status) }}</span>
        </div>

        <ul class="mt-2 space-y-1.5 border-t border-gray-100 pt-3 text-sm text-ink">
          <li v-for="item in order.items" :key="item.id" class="flex items-center justify-between gap-2">
            <span>
              {{ item.quantity }}× {{ item.menuItem.name }}
              <span v-if="showKitchenItemStatus" class="text-[11px] uppercase text-gray-400">({{ itemStatusLabel(item.status) }})</span>
            </span>
            <button
              v-if="isWaiter && isBeverageItem(item) && NEXT_ITEM_STATUS[item.status]"
              type="button"
              class="shrink-0 rounded-lg border border-gray-200 px-2 py-0.5 text-xs font-semibold text-ink-muted hover:border-brand/40 hover:text-brand disabled:opacity-50"
              :disabled="busyItemId === item.id"
              @click="advanceItem(item)"
            >
              {{ $t('staff.orders.markStatus', { status: itemStatusLabel(NEXT_ITEM_STATUS[item.status] ?? null) }) }}
            </button>
          </li>
        </ul>

        <div class="mt-auto flex items-center justify-between gap-2 border-t border-gray-100 pt-3">
          <p class="font-display text-sm font-bold text-ink">{{ Number(order.totalAmount).toLocaleString() }} RWF</p>
          <BaseButton v-if="canAdvanceOrder && NEXT_STATUS[order.status]" :disabled="busyOrderId === order.id" @click="advanceStatus(order)">
            {{ $t('staff.orders.markStatus', { status: orderStatusLabel(NEXT_STATUS[order.status]!) }) }}
          </BaseButton>
        </div>

        <div
          v-if="canAssign && !['COMPLETED', 'CANCELLED'].includes(order.status)"
          class="mt-3 flex items-center gap-2 border-t border-gray-100 pt-3"
        >
          <select v-model="selectedWaiterId[order.id]" class="staff-select flex-1 text-xs">
            <option value="" disabled>{{ $t('staff.orders.assignPlaceholder') }}</option>
            <option v-for="w in onShiftWaiters" :key="w.id" :value="w.id">
              {{ $t('staff.orders.waiterOption', { name: w.fullName, count: w.activeOrders.length }) }}
            </option>
          </select>
          <button
            type="button"
            class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-ink hover:bg-surface disabled:opacity-50"
            :disabled="!selectedWaiterId[order.id] || assigningOrderId === order.id"
            @click="assignWaiter(order)"
          >
            {{ assigningOrderId === order.id ? $t('staff.orders.assigning') : $t('staff.orders.assign') }}
          </button>
        </div>
        <p
          v-if="canAssign && onShiftWaiters.length === 0 && !['COMPLETED', 'CANCELLED'].includes(order.status)"
          class="mt-1 text-[11px] text-gray-400"
        >
          {{ $t('staff.orders.noWaitersOnShift') }}
        </p>

        <div
          v-if="canRecordPayment && order.paymentStatus !== 'PAID' && order.status !== 'CANCELLED'"
          class="mt-3 border-t border-gray-100 pt-3"
        >
          <button
            v-if="payingOrderId !== order.id"
            type="button"
            class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-semibold text-ink hover:border-brand/40 hover:text-brand"
            @click="openPaymentForm(order)"
          >
            {{ $t('staff.orders.recordPayment', { amount: outstanding(order).toLocaleString() }) }}
          </button>

          <div v-else class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
              <input
                v-model.number="paymentDrafts[order.id]!.receivedAmount"
                type="number"
                min="0.01"
                step="0.01"
                class="staff-input w-28 !py-1.5 text-xs"
                :placeholder="$t('staff.orders.receivedPlaceholder')"
              />
              <select v-model="paymentDrafts[order.id]!.paymentMethod" class="staff-select !py-1.5 text-xs">
                <option value="CASH">{{ $t('staff.orders.paymentMethod.cash') }}</option>
                <option value="CARD">{{ $t('staff.orders.paymentMethod.card') }}</option>
                <option value="MOBILE_MONEY">{{ $t('staff.orders.paymentMethod.mobileMoney') }}</option>
                <option value="BANK_TRANSFER">{{ $t('staff.orders.paymentMethod.bankTransfer') }}</option>
              </select>
            </div>
            <input
              v-model="paymentDrafts[order.id]!.paymentReference"
              type="text"
              :placeholder="$t('staff.orders.referencePlaceholder')"
              class="staff-input !py-1.5 text-xs"
            />
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="rounded-xl bg-brand px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-dark disabled:opacity-50"
                :disabled="recordingPayment === order.id || !paymentDrafts[order.id]?.receivedAmount"
                @click="submitPayment(order)"
              >
                {{ recordingPayment === order.id ? $t('staff.orders.recording') : $t('common.confirm') }}
              </button>
              <button type="button" class="text-xs text-ink-muted hover:text-ink" @click="payingOrderId = null">{{ $t('common.cancel') }}</button>
            </div>
          </div>
        </div>

        <div
          v-if="
            (canDiscount && order.paymentStatus !== 'PAID' && order.status !== 'CANCELLED') ||
            (canRefund && refundStillOpen(order) && order.status !== 'CANCELLED')
          "
          class="mt-3 border-t border-gray-100 pt-3"
        >
          <div v-if="adjustingOrderId !== order.id" class="flex flex-wrap gap-2">
            <button
              v-if="canDiscount && order.paymentStatus !== 'PAID'"
              type="button"
              class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-semibold text-ink hover:border-brand/40 hover:text-brand"
              @click="openAdjustment(order, 'discount')"
            >
              {{ $t('staff.orders.discount') }}
            </button>
            <button
              v-if="canRefund && refundStillOpen(order)"
              type="button"
              class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-semibold text-ink hover:border-brand/40 hover:text-brand"
              @click="openAdjustment(order, 'refund')"
            >
              {{ $t('staff.orders.refund') }}
            </button>
          </div>

          <div v-else class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">
              {{ adjustmentKind === 'discount' ? $t('staff.orders.requestDiscount') : $t('staff.orders.requestRefund') }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
              <input
                v-if="adjustmentKind === 'discount'"
                v-model.number="adjustmentDrafts[order.id]!.discountPercent"
                type="number"
                min="0.01"
                max="100"
                step="0.01"
                class="staff-input w-28 !py-1.5 text-xs"
                placeholder="%"
              />
              <input
                v-else
                v-model.number="adjustmentDrafts[order.id]!.refundAmount"
                type="number"
                min="0.01"
                :max="Number(order.paidAmount)"
                step="0.01"
                class="staff-input w-28 !py-1.5 text-xs"
                :placeholder="$t('staff.orders.amountPlaceholder')"
              />
              <span class="text-xs text-ink-muted">{{ adjustmentKind === 'discount' ? $t('staff.orders.percentOfTotal') : 'RWF' }}</span>
            </div>
            <input
              v-model="adjustmentDrafts[order.id]!.reason"
              type="text"
              :placeholder="$t('staff.orders.reasonRequiredPlaceholder')"
              class="staff-input !py-1.5 text-xs"
            />
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="rounded-xl bg-brand px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-dark disabled:opacity-50"
                :disabled="submittingAdjustment === order.id"
                @click="submitAdjustment(order)"
              >
                {{ submittingAdjustment === order.id ? $t('staff.orders.submitting') : $t('staff.orders.submit') }}
              </button>
              <button type="button" class="text-xs text-ink-muted hover:text-ink" @click="closeAdjustment">{{ $t('common.cancel') }}</button>
            </div>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>
