<script setup lang="ts">
import type { PendingAdjustment } from '~/types/adjustment';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['approve_actions'] });

const { t } = useI18n();
const api = useApi();
const items = ref<PendingAdjustment[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const resolving = ref<string | null>(null);

function isCashClose(item: PendingAdjustment): boolean {
  return item.kind === 'CASH_CLOSE' || item.adjustmentType === 'CASH_CLOSE';
}

function typeLabel(item: PendingAdjustment): string {
  if (isCashClose(item)) return t('staff.approvals.typeCashClose');
  if (item.adjustmentType === 'REFUND') return t('staff.orders.refund');
  return t('staff.orders.discount');
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    items.value = await api.get<PendingAdjustment[]>('/staff/adjustments/pending');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.approvals.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function approve(item: PendingAdjustment) {
  resolving.value = item.id;
  try {
    if (isCashClose(item)) {
      await api.post(`/staff/cash/sessions/${item.id}/approve-close`);
    } else {
      await api.post(`/staff/adjustments/${item.id}/approve`);
    }
    await load();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.approvals.approveFailed');
  } finally {
    resolving.value = null;
  }
}

async function reject(item: PendingAdjustment) {
  resolving.value = item.id;
  try {
    if (isCashClose(item)) {
      await api.post(`/staff/cash/sessions/${item.id}/reject-close`);
    } else {
      await api.post(`/staff/adjustments/${item.id}/reject`);
    }
    await load();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.approvals.rejectFailed');
  } finally {
    resolving.value = null;
  }
}

const typeColor: Record<string, string> = {
  DISCOUNT: 'bg-sky-100 text-sky-800',
  REFUND: 'bg-amber-100 text-amber-900',
  CASH_CLOSE: 'bg-violet-100 text-violet-800',
};

const liveRefresh = useLiveRefresh('/staff/events', load, ['approval_needed', 'approval_resolved', 'cash_session_updated']);
onMounted(() => {
  load();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.approvals')" :subtitle="$t('staff.pages.approvalsSub')">
      <template #actions>
        <BaseButton variant="secondary" :disabled="loading" @click="load">{{ $t('common.refresh') }}</BaseButton>
      </template>
    </StaffPageHeader>

    <p v-if="error" class="staff-error">{{ error }}</p>
    <div v-if="loading" class="staff-empty">{{ $t('staff.approvals.loading') }}</div>
    <div v-else-if="items.length === 0" class="staff-empty">{{ $t('staff.approvals.empty') }}</div>

    <div v-else class="space-y-3">
      <article v-for="item in items" :key="item.id" class="staff-card">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="font-display font-bold text-ink">
              {{ isCashClose(item) ? $t('staff.approvals.cashCloseTitle') : item.order?.orderNumber }}
            </p>
            <p class="text-sm text-ink-muted">
              {{
                isCashClose(item)
                  ? $t('staff.approvals.cashCloseBy', { name: item.requestedByName })
                  : $t('staff.approvals.tableRequestedBy', { table: item.order?.tableNumber, name: item.requestedByName })
              }}
              <span
                class="staff-chip ml-1"
                :class="item.requestedByOnShift ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500'"
              >
                {{ item.requestedByOnShift ? $t('staff.approvals.onShift') : $t('staff.approvals.offShift') }}
              </span>
            </p>
          </div>
          <span class="staff-chip" :class="typeColor[item.adjustmentType]">{{ typeLabel(item) }}</span>
        </div>

        <template v-if="isCashClose(item) && item.cashSession">
          <p class="mt-2 font-display text-lg font-bold text-ink">{{ item.cashSession.closingBalance.toLocaleString() }} RWF</p>
          <p class="mt-1 text-sm text-ink-muted">{{ item.reason }}</p>
          <p class="mt-1 text-xs text-gray-400">
            {{ $t('staff.approvals.cashOpenedAt', { date: new Date(item.cashSession.openedAt).toLocaleString() }) }}
            · {{ $t('staff.approvals.cashExpected', { amount: item.cashSession.expectedBalance.toLocaleString() }) }}
            · {{ $t('staff.approvals.cashVariance', { amount: item.cashSession.variance.toLocaleString() }) }}
          </p>
        </template>
        <template v-else>
          <p class="mt-2 font-display text-lg font-bold text-ink">{{ item.amount.toLocaleString() }} RWF</p>
          <p class="mt-1 text-sm text-ink-muted">{{ item.reason }}</p>
          <p class="mt-1 text-xs text-gray-400">
            {{ new Date(item.createdAt).toLocaleString() }}
            · {{ $t('staff.approvals.orderTotal', { amount: (item.order?.totalAmount ?? 0).toLocaleString() }) }}
            · {{ $t('staff.approvals.paidAmount', { amount: (item.order?.paidAmount ?? 0).toLocaleString() }) }}
          </p>
        </template>

        <div v-if="item.requestedByActiveOrders.length > 0" class="mt-2 rounded-lg bg-surface px-3 py-2">
          <p class="text-xs font-semibold text-ink-muted">
            {{ $t('staff.approvals.currentOrders', { count: item.requestedByActiveOrders.length }) }}
          </p>
          <ul class="mt-1 space-y-0.5">
            <li v-for="order in item.requestedByActiveOrders" :key="order.id" class="text-xs text-ink-muted">
              {{
                $t('staff.approvals.currentOrderLine', {
                  table: order.tableNumber,
                  orderNumber: order.orderNumber,
                  status: order.status,
                })
              }}
            </li>
          </ul>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3">
          <BaseButton :disabled="resolving === item.id" @click="approve(item)">
            {{ resolving === item.id ? $t('staff.approvals.working') : $t('staff.approvals.approve') }}
          </BaseButton>
          <button
            type="button"
            class="rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
            :disabled="resolving === item.id"
            @click="reject(item)"
          >
            {{ $t('staff.approvals.reject') }}
          </button>
        </div>
      </article>
    </div>
  </div>
</template>
