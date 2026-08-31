<script setup lang="ts">
import type { SalesReport, TopMenuItem } from '~/types/report';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['view_reports'], features: ['analytics'] });

const { t } = useI18n();
const api = useApi();

function isoDate(d: Date): string {
  return d.toISOString().slice(0, 10);
}

const today = new Date();
const thirtyDaysAgo = new Date(today.getTime() - 29 * 24 * 60 * 60 * 1000);

const startDate = ref(isoDate(thirtyDaysAgo));
const endDate = ref(isoDate(today));

const sales = ref<SalesReport | null>(null);
const topItems = ref<TopMenuItem[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const params = new URLSearchParams({ startDate: startDate.value, endDate: `${endDate.value}T23:59:59.999Z` });
    [sales.value, topItems.value] = await Promise.all([
      api.get<SalesReport>(`/staff/reports/sales?${params.toString()}`),
      api.get<TopMenuItem[]>(`/staff/reports/top-items?${params.toString()}`),
    ]);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.reports.loadFailed');
  } finally {
    loading.value = false;
  }
}

const revenueByDayRows = computed(() => {
  if (!sales.value) return [];
  const entries = Object.entries(sales.value.revenueByDay).sort(([a], [b]) => (a < b ? 1 : -1));
  const max = Math.max(1, ...entries.map(([, v]) => v));
  return entries.map(([day, amount]) => ({ day, amount, pct: Math.round((amount / max) * 100) }));
});

onMounted(load);
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.reports')" :subtitle="$t('staff.pages.reportsSub')" />

    <p v-if="error" class="staff-error">{{ error }}</p>

    <div class="mb-5 flex flex-wrap items-end gap-3">
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.reports.from') }}</label>
        <input v-model="startDate" type="date" class="staff-input" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.reports.to') }}</label>
        <input v-model="endDate" type="date" class="staff-input" />
      </div>
      <BaseButton :disabled="loading" @click="load">{{ loading ? $t('common.loading') : $t('staff.reports.runReport') }}</BaseButton>
    </div>

    <div v-if="loading && !sales" class="staff-empty">{{ $t('staff.reports.loadingState') }}</div>

    <template v-if="sales">
      <div class="mb-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="staff-card">
          <p class="text-sm text-ink-muted">{{ $t('staff.reports.totalRevenue') }}</p>
          <p class="mt-1 font-display text-2xl font-bold text-ink">{{ sales.totalRevenue.toLocaleString() }}</p>
        </div>
        <div class="staff-card">
          <p class="text-sm text-ink-muted">{{ $t('staff.nav.orders') }}</p>
          <p class="mt-1 font-display text-2xl font-bold text-ink">{{ sales.orderCount }}</p>
        </div>
        <div class="staff-card">
          <p class="text-sm text-ink-muted">{{ $t('staff.reports.avgOrderValue') }}</p>
          <p class="mt-1 font-display text-2xl font-bold text-ink">{{ Math.round(sales.averageOrderValue).toLocaleString() }}</p>
        </div>
        <div class="staff-card">
          <p class="text-sm text-ink-muted">{{ $t('staff.reports.averageRating') }}</p>
          <p class="mt-1 font-display text-2xl font-bold text-ink">
            {{ sales.averageRating != null ? sales.averageRating.toFixed(1) : '—' }}
          </p>
          <p v-if="sales.ratingCount" class="mt-0.5 text-[11px] text-ink-muted">{{ sales.ratingCount }}</p>
        </div>
      </div>

      <div class="mb-5 grid gap-4 lg:grid-cols-2">
        <div class="staff-card">
          <h2 class="mb-3 font-display text-sm font-bold text-ink">{{ $t('staff.reports.revenueByDay') }}</h2>
          <p v-if="revenueByDayRows.length === 0" class="text-sm text-ink-muted">{{ $t('staff.reports.noRevenueRange') }}</p>
          <div v-else class="max-h-72 space-y-1.5 overflow-y-auto">
            <div v-for="row in revenueByDayRows" :key="row.day" class="flex items-center gap-2 text-xs">
              <span class="w-20 shrink-0 text-ink-muted">{{ row.day }}</span>
              <div class="h-3 flex-1 rounded-full bg-surface">
                <div class="h-3 rounded-full bg-brand" :style="{ width: `${row.pct}%` }" />
              </div>
              <span class="w-20 shrink-0 text-right text-ink">{{ row.amount.toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <div class="staff-card">
          <h2 class="mb-3 font-display text-sm font-bold text-ink">{{ $t('staff.reports.revenueByPaymentMethod') }}</h2>
          <p v-if="Object.keys(sales.revenueByPaymentMethod).length === 0" class="text-sm text-ink-muted">{{ $t('staff.reports.noPaymentsRange') }}</p>
          <div v-else class="space-y-2">
            <div v-for="[method, amount] in Object.entries(sales.revenueByPaymentMethod)" :key="method" class="flex items-center justify-between text-sm">
              <span class="capitalize text-ink-muted">{{ method.toLowerCase().replace('_', ' ') }}</span>
              <span class="font-semibold text-ink">{{ amount.toLocaleString() }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="staff-card mb-5">
        <h2 class="mb-3 font-display text-sm font-bold text-ink">{{ $t('staff.reports.guestsBySource') }}</h2>
        <p v-if="!sales.guestsBySource || Object.keys(sales.guestsBySource).length === 0" class="text-sm text-ink-muted">
          {{ $t('staff.reports.noGuestSources') }}
        </p>
        <div v-else class="space-y-2">
          <div v-for="[source, count] in Object.entries(sales.guestsBySource)" :key="source" class="flex items-center justify-between text-sm">
            <span class="text-ink-muted">{{ $t(`heardAbout.guest.${source}`) }}</span>
            <span class="font-semibold text-ink">{{ count }}</span>
          </div>
        </div>
      </div>

      <div class="staff-card mb-5">
        <h2 class="mb-3 font-display text-sm font-bold text-ink">{{ $t('staff.reports.guestComments') }}</h2>
        <p v-if="!sales.guestComments || sales.guestComments.length === 0" class="text-sm text-ink-muted">
          {{ $t('staff.reports.noGuestComments') }}
        </p>
        <ul v-else class="max-h-80 space-y-3 overflow-y-auto">
          <li v-for="row in sales.guestComments" :key="`${row.orderNumber}-${row.createdAt}`" class="border-t border-gray-100 pt-3 first:border-t-0 first:pt-0">
            <p class="text-xs text-ink-muted">
              {{ $t('staff.reports.tableOrder', { table: row.tableNumber, order: row.orderNumber }) }}
              <span v-if="row.rating" class="ml-1 font-semibold text-brand">★ {{ row.rating }}</span>
            </p>
            <p class="mt-1 text-sm text-ink">{{ row.comment }}</p>
          </li>
        </ul>
      </div>

      <div class="staff-card">
        <h2 class="mb-3 font-display text-sm font-bold text-ink">{{ $t('staff.reports.topMenuItems') }}</h2>
        <p v-if="topItems.length === 0" class="text-sm text-ink-muted">{{ $t('staff.reports.noItemsRange') }}</p>
        <table v-else class="w-full text-sm">
          <thead class="text-left text-xs uppercase tracking-wide text-ink-muted">
            <tr>
              <th class="pb-2">{{ $t('staff.reports.colItem') }}</th>
              <th class="pb-2 text-right">{{ $t('staff.reports.colQtySold') }}</th>
              <th class="pb-2 text-right">{{ $t('staff.reports.colRevenue') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in topItems" :key="item.menuItemId" class="border-t border-gray-100">
              <td class="py-1.5 text-ink">{{ item.name }}</td>
              <td class="py-1.5 text-right text-ink">{{ item.quantitySold }}</td>
              <td class="py-1.5 text-right text-ink">{{ item.revenue.toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>
