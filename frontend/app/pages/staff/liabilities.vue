<script setup lang="ts">
import type { Liability, LiabilityStat, WaivedByStaff } from '~/types/liability';

definePageMeta({ middleware: 'staff-auth', layout: 'staff' });

const { t } = useI18n();
const api = useApi();
const auth = useAuthStore();
const liabilities = ref<Liability[]>([]);
const stats = ref<LiabilityStat[]>([]);
const mySummary = ref<LiabilityStat[]>([]);
const waivedByStaff = ref<WaivedByStaff[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const statusFilter = ref('');

// Backend gates waive/mark-loss behind `manage_staff`, which only ADMIN
// holds — MANAGER is deliberately excluded too (backend/prisma/seed/permissions.ts).
const canResolve = computed(() => auth.staff.value?.role === 'ADMIN');
const canSeeAll = computed(() => auth.staff.value?.role === 'ADMIN' || auth.staff.value?.role === 'MANAGER');

const resolving = ref<string | null>(null);
const reasonDrafts = ref<Record<string, string>>({});

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const params = statusFilter.value ? `?status=${statusFilter.value}` : '';
    liabilities.value = await api.get<Liability[]>(`/staff/liabilities${params}`);
    mySummary.value = await api.get<LiabilityStat[]>('/staff/liabilities/my-summary');
    if (canSeeAll.value) {
      stats.value = await api.get<LiabilityStat[]>('/staff/liabilities/stats');
      waivedByStaff.value = await api.get<WaivedByStaff[]>('/staff/liabilities/waived-by-staff');
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.liabilities.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function waive(l: Liability) {
  const reason = reasonDrafts.value[l.id]?.trim();
  if (!reason) {
    error.value = t('staff.liabilities.waiveReasonRequired');
    return;
  }
  resolving.value = l.id;
  try {
    await api.post(`/staff/liabilities/${l.id}/waive`, { reason });
    await load();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.liabilities.waiveFailed');
  } finally {
    resolving.value = null;
  }
}

async function markLoss(l: Liability) {
  const reason = reasonDrafts.value[l.id]?.trim();
  if (!reason) {
    error.value = t('staff.liabilities.lossReasonRequired');
    return;
  }
  resolving.value = l.id;
  try {
    await api.post(`/staff/liabilities/${l.id}/mark-loss`, { reason });
    await load();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.liabilities.markLossFailed');
  } finally {
    resolving.value = null;
  }
}

const statusColor: Record<string, string> = {
  ACTIVE: 'bg-amber-100 text-amber-900',
  CLEARED: 'bg-emerald-100 text-emerald-800',
  LOSS: 'bg-red-100 text-red-700',
  WAIVED: 'bg-gray-100 text-gray-600',
  INVESTIGATING: 'bg-violet-100 text-violet-800',
};

// 10,000 RWF waiver-approval threshold, mirroring liability.service.ts::LIABILITY_WAIVER_APPROVAL_THRESHOLD —
// purely informational here so the person waiving knows it'll queue for manager approval.
const WAIVER_APPROVAL_THRESHOLD = 10_000;

const pageTitle = computed(() => (canSeeAll.value ? t('staff.liabilities.titleAll') : t('staff.liabilities.titleMine')));

function statusLabel(status: string): string {
  if (status === 'ACTIVE') return t('common.active');
  return t(`staff.liabilities.status.${status.toLowerCase()}`);
}

const liveRefresh = useLiveRefresh('/staff/events', load, ['order_status', 'liability_updated']);
onMounted(() => {
  load();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="pageTitle" :subtitle="$t('staff.pages.liabilitiesSub')">
      <template #actions>
        <BaseButton variant="secondary" :disabled="loading" @click="load">{{ $t('common.refresh') }}</BaseButton>
      </template>
    </StaffPageHeader>

    <p v-if="error" class="staff-error">{{ error }}</p>

    <div v-if="mySummary.length > 0" class="mb-5">
      <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ $t('staff.liabilities.mySummaryTitle') }}</p>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
        <div v-for="s in mySummary" :key="s.status" class="staff-card">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ statusLabel(s.status) }}</p>
          <p class="font-display text-lg font-bold text-ink">{{ s.count }}</p>
          <p class="text-xs text-ink-muted">{{ s.totalAmount.toLocaleString() }}</p>
        </div>
      </div>
    </div>

    <div v-if="stats.length > 0" class="mb-5">
      <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ pageTitle }}</p>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
        <div v-for="s in stats" :key="s.status" class="staff-card">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ statusLabel(s.status) }}</p>
          <p class="font-display text-lg font-bold text-ink">{{ s.count }}</p>
          <p class="text-xs text-ink-muted">{{ s.totalAmount.toLocaleString() }}</p>
        </div>
      </div>
    </div>

    <div v-if="canSeeAll && waivedByStaff.length > 0" class="mb-5">
      <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ $t('staff.liabilities.waivedByStaffTitle') }}</p>
      <div class="staff-card divide-y divide-gray-100 !p-0">
        <div v-for="w in waivedByStaff" :key="w.waiterId" class="flex items-center justify-between gap-2 px-4 py-2.5">
          <div>
            <p class="text-sm font-semibold text-ink">{{ w.fullName }}</p>
            <p class="text-xs text-ink-muted">@{{ w.username }}</p>
          </div>
          <div class="text-right">
            <p class="font-display text-sm font-bold text-ink">{{ w.totalAmount.toLocaleString() }}</p>
            <p class="text-xs text-ink-muted">{{ w.count }}×</p>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <select v-model="statusFilter" class="staff-select" @change="load">
        <option value="">{{ $t('staff.liabilities.allStatuses') }}</option>
        <option value="ACTIVE">{{ $t('common.active') }}</option>
        <option value="CLEARED">{{ $t('staff.liabilities.status.cleared') }}</option>
        <option value="WAIVED">{{ $t('staff.liabilities.status.waived') }}</option>
        <option value="LOSS">{{ $t('staff.liabilities.status.loss') }}</option>
        <option value="INVESTIGATING">{{ $t('staff.liabilities.status.investigating') }}</option>
      </select>
    </div>

    <div v-if="loading" class="staff-empty">{{ $t('staff.liabilities.loading') }}</div>
    <div v-else-if="liabilities.length === 0" class="staff-empty">{{ $t('staff.liabilities.empty') }}</div>

    <div v-else class="space-y-3">
      <article v-for="l in liabilities" :key="l.id" class="staff-card">
        <div class="flex items-center justify-between gap-2">
          <div>
            <p class="font-display font-bold text-ink">{{ l.order.orderNumber }}</p>
            <p v-if="canSeeAll" class="text-xs text-ink-muted">{{ l.waiter.fullName }}</p>
          </div>
          <span class="staff-chip" :class="statusColor[l.status]">{{ statusLabel(l.status) }}</span>
        </div>
        <p class="mt-1 font-display text-sm font-bold text-ink">{{ Number(l.orderAmount).toLocaleString() }}</p>
        <p class="mt-1 text-xs text-gray-400">
          {{ $t('staff.liabilities.openedAt', { date: new Date(l.liabilityCreatedAt).toLocaleString() }) }}
          <span v-if="Number(l.orderAmount) > WAIVER_APPROVAL_THRESHOLD && l.status === 'ACTIVE'">
            · {{ $t('staff.liabilities.overThreshold', { amount: WAIVER_APPROVAL_THRESHOLD.toLocaleString() }) }}
          </span>
        </p>
        <p v-if="l.notes" class="mt-1 text-xs text-ink-muted">{{ $t('staff.liabilities.note', { notes: l.notes }) }}</p>

        <div v-if="canResolve && l.status === 'ACTIVE'" class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3">
          <input v-model="reasonDrafts[l.id]" type="text" :placeholder="$t('staff.liabilities.reasonPlaceholder')" class="staff-input flex-1 !py-1.5 text-xs" />
          <button
            type="button"
            class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-semibold text-ink hover:bg-surface disabled:opacity-50"
            :disabled="resolving === l.id"
            @click="waive(l)"
          >
            {{ $t('staff.liabilities.waive') }}
          </button>
          <button
            type="button"
            class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
            :disabled="resolving === l.id"
            @click="markLoss(l)"
          >
            {{ $t('staff.liabilities.markLoss') }}
          </button>
        </div>
      </article>
    </div>
  </div>
</template>
