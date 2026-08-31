<script setup lang="ts">
import type { ProfitLoss } from '~/types/report';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['view_financials'], features: ['analytics'] });

const { t } = useI18n();
const api = useApi();
const auth = useAuthStore();

// Backend gates this behind `view_financials`, granted to ADMIN only among
// restaurant-scoped roles (backend/prisma/seed/permissions.ts) — a
// restaurant's cost/margin picture is deliberately not shown to managers.
const isOwner = computed(() => auth.staff.value?.role === 'ADMIN');

const period = ref<'daily' | 'weekly' | 'monthly'>('daily');
const result = ref<ProfitLoss | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

async function load() {
  loading.value = true;
  error.value = null;
  try {
    result.value = await api.get<ProfitLoss>(`/staff/reports/profit-loss?period=${period.value}`);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.financials.loadFailed');
  } finally {
    loading.value = false;
  }
}

const statusLabel = computed(() => {
  if (!result.value) return '';
  return t(`staff.financials.status.${result.value.status.toLowerCase()}`);
});

const statusColor = computed(() => {
  if (!result.value) return '';
  if (result.value.status === 'PROFIT') return 'bg-emerald-100 text-emerald-800';
  if (result.value.status === 'LOSS') return 'bg-red-100 text-red-700';
  return 'bg-gray-100 text-gray-600';
});

const costRows = computed(() => {
  if (!result.value) return [];
  const b = result.value.costBreakdown;
  return [
    { label: t('staff.financials.expenses'), value: b.expenses },
    { label: t('staff.financials.withdrawals'), value: b.withdrawals },
    { label: t('staff.financials.refunds'), value: b.refunds },
    { label: t('staff.financials.liabilityLoss'), value: b.liabilityLoss },
    { label: t('staff.financials.liabilityWaived'), value: b.liabilityWaived },
  ].filter((row) => row.value > 0);
});

const liveRefresh = useLiveRefresh('/staff/events', load);
onMounted(() => {
  if (isOwner.value) {
    load();
    liveRefresh.start();
  } else {
    loading.value = false;
  }
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.financials')" :subtitle="$t('staff.pages.financialsSub')" />

    <div v-if="!isOwner" class="staff-empty">{{ $t('staff.financials.ownerOnly') }}</div>

    <template v-else>
      <p v-if="error" class="staff-error">{{ error }}</p>

      <div class="mb-5 flex gap-2">
        <button
          v-for="p in (['daily', 'weekly', 'monthly'] as const)"
          :key="p"
          type="button"
          class="rounded-xl border px-3 py-1.5 text-sm font-semibold transition"
          :class="period === p ? 'border-brand bg-brand text-white' : 'border-gray-200 text-ink-muted hover:border-brand/40'"
          @click="period = p; load()"
        >
          {{ $t(`staff.financials.period.${p}`) }}
        </button>
      </div>

      <div v-if="loading" class="staff-empty">{{ $t('staff.financials.loading') }}</div>

      <template v-else-if="result">
        <div class="staff-card mb-5">
          <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $t('staff.financials.netResult') }}</p>
            <span class="staff-chip" :class="statusColor">{{ statusLabel }}</span>
          </div>
          <p
            class="mt-2 font-display text-3xl font-extrabold tracking-tight"
            :class="result.net > 0 ? 'text-emerald-700' : result.net < 0 ? 'text-red-600' : 'text-ink'"
          >
            {{ result.net > 0 ? '+' : '' }}{{ result.net.toLocaleString() }} RWF
          </p>
          <p class="mt-1 text-sm text-ink-muted">
            {{ $t('staff.financials.marginLabel', { pct: result.marginPct }) }}
          </p>
        </div>

        <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-2">
          <div class="staff-card">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ $t('staff.financials.revenue') }}</p>
            <p class="font-display text-xl font-bold text-ink">{{ result.revenue.toLocaleString() }}</p>
          </div>
          <div class="staff-card">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ $t('staff.financials.costs') }}</p>
            <p class="font-display text-xl font-bold text-ink">{{ result.costs.toLocaleString() }}</p>
          </div>
        </div>

        <div v-if="costRows.length > 0" class="staff-card">
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $t('staff.financials.costBreakdown') }}</p>
          <div class="divide-y divide-gray-100">
            <div v-for="row in costRows" :key="row.label" class="flex items-center justify-between py-1.5 text-sm">
              <span class="text-ink-muted">{{ row.label }}</span>
              <span class="font-semibold text-ink">{{ row.value.toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <p class="mt-4 text-xs text-ink-muted">{{ $t('staff.financials.methodologyNote') }}</p>
      </template>
    </template>
  </div>
</template>
