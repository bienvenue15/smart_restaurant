<script setup lang="ts">
import type { ActivityLogRow } from '~/types/activityLog';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['view_activity_log'] });

const { t } = useI18n();
const api = useApi();
const rows = ref<ActivityLogRow[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const search = ref('');
const sourceFilter = ref<'all' | 'activity' | 'audit'>('all');

const filtered = computed(() => {
  if (sourceFilter.value === 'all') return rows.value;
  return rows.value.filter((r) => r.source === sourceFilter.value);
});

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const params = new URLSearchParams();
    if (search.value.trim()) params.set('search', search.value.trim());
    const qs = params.toString();
    rows.value = await api.get<ActivityLogRow[]>(`/staff/activity-log${qs ? `?${qs}` : ''}`);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.activityLog.loadFailed');
  } finally {
    loading.value = false;
  }
}

// No dedicated "activity logged" event exists — nearly every staff action
// (clock in/out, menu edits, table changes, cash sessions, orders, calls)
// already publishes a staff-channel event, so refreshing on any of them
// keeps this page current without needing a bespoke event type.
const liveRefresh = useLiveRefresh('/staff/events', load);
onMounted(() => {
  load();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.activityLog')" :subtitle="$t('staff.pages.activitySub')">
      <template #actions>
        <BaseButton variant="secondary" :disabled="loading" @click="load">{{ $t('common.refresh') }}</BaseButton>
      </template>
    </StaffPageHeader>

    <p v-if="error" class="staff-error">{{ error }}</p>

    <div class="mb-4 flex flex-wrap items-center gap-2">
      <input
        v-model="search"
        type="text"
        :placeholder="$t('staff.activityLog.searchPlaceholder')"
        class="staff-input max-w-xs"
        @keyup.enter="load"
      />
      <BaseButton variant="secondary" :disabled="loading" @click="load">{{ $t('common.search') }}</BaseButton>
      <select v-model="sourceFilter" class="staff-select">
        <option value="all">{{ $t('staff.activityLog.allSources') }}</option>
        <option value="activity">{{ $t('staff.activityLog.staffActivity') }}</option>
        <option value="audit">{{ $t('staff.activityLog.auditTrail') }}</option>
      </select>
      <span class="text-xs text-ink-muted">{{ $t('staff.activityLog.eventsCount', { count: filtered.length }) }}</span>
    </div>

    <div v-if="loading" class="staff-empty">{{ $t('staff.activityLog.loading') }}</div>
    <div v-else-if="filtered.length === 0" class="staff-empty">{{ $t('staff.activityLog.noResults') }}</div>

    <div v-else class="staff-card overflow-x-auto !p-0">
      <table class="w-full text-sm">
        <thead class="border-b border-gray-100 bg-surface text-left text-[11px] font-semibold uppercase tracking-wide text-ink-muted">
          <tr>
            <th class="px-4 py-2.5">{{ $t('staff.activityLog.colTime') }}</th>
            <th class="px-4 py-2.5">{{ $t('staff.activityLog.colStaff') }}</th>
            <th class="px-4 py-2.5">{{ $t('staff.activityLog.colAction') }}</th>
            <th class="px-4 py-2.5">{{ $t('staff.activityLog.colDetail') }}</th>
            <th class="px-4 py-2.5">{{ $t('staff.activityLog.colSource') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in filtered" :key="`${row.source}-${row.id}`" class="border-b border-gray-50 last:border-0">
            <td class="whitespace-nowrap px-4 py-2.5 text-ink-muted">{{ new Date(row.createdAt).toLocaleString() }}</td>
            <td class="px-4 py-2.5 font-medium text-ink">{{ row.staffName }}</td>
            <td class="px-4 py-2.5 text-ink">{{ row.action }}</td>
            <td class="px-4 py-2.5 text-ink-muted">{{ row.description ?? '' }}</td>
            <td class="px-4 py-2.5">
              <span class="staff-chip" :class="row.source === 'audit' ? 'bg-violet-100 text-violet-800' : 'bg-gray-100 text-gray-600'">
                {{ row.source }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
