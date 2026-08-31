<script setup lang="ts">
interface AuditRow {
  source: 'activity' | 'audit';
  id: string;
  restaurantId: string | null;
  restaurantName: string | null;
  staffName: string;
  action: string;
  description: string | null;
  createdAt: string;
}

interface RestaurantOption {
  id: string;
  name: string;
}

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const { t } = useI18n();
const api = useApi();
const rows = ref<AuditRow[]>([]);
const restaurantOptions = ref<RestaurantOption[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const search = ref('');
const restaurantId = ref('');
const dateFrom = ref('');
const dateTo = ref('');

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (restaurantId.value) params.set('restaurantId', restaurantId.value);
    rows.value = await api.get<AuditRow[]>(`/admin/audit-log?${params.toString()}`);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.auditLog.loadFailed');
  } finally {
    loading.value = false;
  }
}

// The backend merges two cross-tenant tables server-side with no
// date-range param (admin.service.ts::getPlatformAuditLog) — narrowed
// client-side here, same as the small filters elsewhere in this console.
const filteredRows = computed(() => {
  const from = dateFrom.value ? new Date(dateFrom.value).getTime() : null;
  const to = dateTo.value ? new Date(dateTo.value).getTime() + 24 * 60 * 60 * 1000 : null;
  return rows.value.filter((r) => {
    const t = new Date(r.createdAt).getTime();
    if (from !== null && t < from) return false;
    if (to !== null && t >= to) return false;
    return true;
  });
});

onMounted(async () => {
  await load();
  try {
    restaurantOptions.value = await api.get<RestaurantOption[]>('/admin/restaurants');
  } catch {
    // Restaurant filter is a nice-to-have — the log itself already loaded.
  }
});
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">{{ $t('admin.nav.auditLog') }}</h1>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

    <div class="mb-4 flex flex-wrap items-center gap-2">
      <input v-model="search" type="text" :placeholder="$t('admin.auditLog.searchPlaceholder')" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm" @keyup.enter="load" />
      <select v-model="restaurantId" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" @change="load">
        <option value="">{{ $t('admin.auditLog.allRestaurants') }}</option>
        <option v-for="r in restaurantOptions" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
      <input v-model="dateFrom" type="date" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      <span class="text-xs text-gray-400">{{ $t('admin.auditLog.dateTo') }}</span>
      <input v-model="dateTo" type="date" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      <BaseButton variant="secondary" :disabled="loading" @click="load">{{ $t('common.search') }}</BaseButton>
      <span class="text-xs text-gray-400">{{ $t('admin.auditLog.eventCount', { count: filteredRows.length }) }}</span>
    </div>

    <p v-if="loading" class="text-sm text-gray-500">{{ $t('common.loading') }}</p>
    <p v-else-if="filteredRows.length === 0" class="text-sm text-gray-500">{{ $t('admin.auditLog.empty') }}</p>

    <div v-else class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase text-gray-500">
          <tr>
            <th class="px-3 py-2">{{ $t('admin.auditLog.time') }}</th>
            <th class="px-3 py-2">{{ $t('admin.auditLog.restaurant') }}</th>
            <th class="px-3 py-2">{{ $t('admin.auditLog.staff') }}</th>
            <th class="px-3 py-2">{{ $t('admin.auditLog.action') }}</th>
            <th class="px-3 py-2">{{ $t('admin.auditLog.detail') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in filteredRows" :key="`${r.source}-${r.id}`" class="border-b border-gray-100 last:border-0">
            <td class="whitespace-nowrap px-3 py-2 text-gray-500">{{ new Date(r.createdAt).toLocaleString() }}</td>
            <td class="px-3 py-2">{{ r.restaurantName ?? '—' }}</td>
            <td class="px-3 py-2">{{ r.staffName }}</td>
            <td class="px-3 py-2 font-medium text-gray-800">{{ r.action }}</td>
            <td class="px-3 py-2 text-gray-500">{{ r.description ?? '' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
