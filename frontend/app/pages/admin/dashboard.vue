<script setup lang="ts">
import type { PlatformStats } from '~/types/admin';

interface PlatformAnalytics {
  revenueByDay: Record<string, number>;
  byRestaurant: { restaurantId: string; name: string; orders: number; revenue: number }[];
}

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const { t } = useI18n();
const api = useApi();
const adminPath = useAdminPath();
const stats = ref<PlatformStats | null>(null);
const analytics = ref<PlatformAnalytics | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

async function loadStats() {
  loading.value = true;
  error.value = null;
  try {
    [stats.value, analytics.value] = await Promise.all([
      api.get<PlatformStats>('/admin/stats'),
      api.get<PlatformAnalytics>('/admin/analytics'),
    ]);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.dashboard.loadFailed');
  } finally {
    loading.value = false;
  }
}

const tiles = computed(() => [
  { label: t('admin.dashboard.totalRestaurants'), value: stats.value?.totalRestaurants ?? '—', to: adminPath.path('restaurants') },
  { label: t('admin.dashboard.activeRestaurants'), value: stats.value?.activeRestaurants ?? '—', to: { path: adminPath.path('restaurants'), query: { status: 'active' } } },
  { label: t('admin.dashboard.totalOrders'), value: stats.value?.totalOrders ?? '—', to: undefined },
  { label: t('admin.dashboard.todayOrders'), value: stats.value?.todayOrders ?? '—', to: undefined },
  { label: t('admin.dashboard.monthRevenue'), value: stats.value ? stats.value.monthRevenue.toLocaleString() : '—', to: undefined },
]);

const revenueByDayRows = computed(() => {
  if (!analytics.value) return [];
  const entries = Object.entries(analytics.value.revenueByDay).sort(([a], [b]) => (a < b ? 1 : -1));
  const max = Math.max(1, ...entries.map(([, v]) => v));
  return entries.map(([day, amount]) => ({ day, amount, pct: Math.round((amount / max) * 100) }));
});

const liveRefresh = useLiveRefresh('/staff/events', loadStats);
onMounted(() => {
  loadStats();
  liveRefresh.start();
});
</script>

<template>
  <div class="p-6">
    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-ink-muted">{{ $t('common.loading') }}</p>

    <template v-else>
      <div class="mb-3.5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <component
          :is="tile.to ? 'NuxtLink' : 'div'"
          v-for="tile in tiles"
          :key="tile.label"
          :to="tile.to"
          class="rounded-[14px] border border-line bg-card p-4"
          :class="tile.to ? 'transition hover:-translate-y-0.5 hover:border-brand' : ''"
        >
          <p class="text-[11.5px] font-bold text-ink-muted">{{ tile.label }}</p>
          <p class="mt-1 font-display text-[22px] font-bold text-admin-deep">{{ tile.value }}</p>
        </component>
      </div>

      <div
        v-if="stats?.restaurantsBySource && Object.keys(stats.restaurantsBySource).length"
        class="mt-3 rounded-[14px] border border-line bg-card p-4"
      >
        <h2 class="mb-3 font-body text-[14.5px] font-semibold text-admin-deep">{{ $t('admin.dashboard.restaurantsBySource') }}</h2>
        <div class="space-y-2">
          <div
            v-for="[source, count] in Object.entries(stats.restaurantsBySource).sort((a, b) => b[1] - a[1])"
            :key="source"
            class="flex items-center justify-between text-sm"
          >
            <span class="text-gray-600">
              {{ source === 'UNKNOWN' ? $t('heardAbout.unknown') : $t(`heardAbout.platform.${source}`) }}
            </span>
            <span class="font-semibold text-gray-900">{{ count }}</span>
          </div>
        </div>
      </div>

      <div class="mt-3 grid gap-3 lg:grid-cols-2">
        <div class="rounded-[14px] border border-line bg-card p-4">
          <h2 class="mb-3 font-body text-[14.5px] font-semibold text-admin-deep">{{ $t('admin.dashboard.revenueChartTitle') }}</h2>
          <p v-if="revenueByDayRows.length === 0" class="text-sm text-ink-muted">{{ $t('admin.dashboard.noCompletedOrders') }}</p>
          <div v-else class="max-h-72 space-y-1.5 overflow-y-auto">
            <div v-for="row in revenueByDayRows" :key="row.day" class="flex items-center gap-2 text-xs">
              <span class="w-20 shrink-0 text-ink-muted">{{ row.day }}</span>
              <div class="h-3 flex-1 rounded bg-sand">
                <div class="h-3 rounded bg-emerald" :style="{ width: `${row.pct}%` }" />
              </div>
              <span class="w-20 shrink-0 text-right text-ink">{{ row.amount.toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <div class="rounded-[14px] border border-line bg-card p-4">
          <h2 class="mb-3 font-body text-[14.5px] font-semibold text-admin-deep">{{ $t('admin.dashboard.byRestaurantTitle') }}</h2>
          <p v-if="!analytics?.byRestaurant.length" class="text-sm text-ink-muted">{{ $t('admin.dashboard.noCompletedOrders') }}</p>
          <table v-else class="w-full text-sm">
            <thead class="text-left text-[10.5px] uppercase tracking-wider text-ink-muted">
              <tr>
                <th class="pb-2">{{ $t('admin.dashboard.restaurant') }}</th>
                <th class="pb-2 text-right">{{ $t('admin.dashboard.orders') }}</th>
                <th class="pb-2 text-right">{{ $t('admin.dashboard.revenue') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in analytics!.byRestaurant" :key="r.restaurantId" class="border-t border-[#f0eee6]">
                <td class="py-2.5">{{ r.name }}</td>
                <td class="py-2.5 text-right">{{ r.orders }}</td>
                <td class="py-2.5 text-right">{{ r.revenue.toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
