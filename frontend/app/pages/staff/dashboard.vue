<script setup lang="ts">
import type { DashboardStats, ShiftStatus } from '~/types/staff';

definePageMeta({ middleware: 'staff-auth' });

const api = useApi();
const auth = useAuthStore();

const stats = ref<DashboardStats | null>(null);
const shiftStatus = ref<ShiftStatus | null>(null);
const loading = ref(true);
const shiftBusy = ref(false);
const error = ref<string | null>(null);

async function loadDashboard() {
  loading.value = true;
  error.value = null;
  try {
    [stats.value, shiftStatus.value] = await Promise.all([
      api.get<DashboardStats>('/staff/reports/dashboard'),
      api.get<ShiftStatus>('/staff/users/me/shift-status'),
    ]);
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load dashboard';
  } finally {
    loading.value = false;
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
    shiftStatus.value = await api.get<ShiftStatus>('/staff/users/me/shift-status');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to update shift status';
  } finally {
    shiftBusy.value = false;
  }
}

const statTiles = computed(() => [
  { label: "Today's orders", value: stats.value?.todayOrders ?? '—' },
  { label: "Today's revenue", value: stats.value ? stats.value.todayRevenue.toLocaleString() : '—' },
  { label: 'Pending orders', value: stats.value?.pendingOrders ?? '—' },
  { label: 'Active tables', value: stats.value?.activeTables ?? '—' },
  { label: 'Pending waiter calls', value: stats.value?.pendingWaiterCalls ?? '—' },
  { label: 'Pending approvals', value: stats.value?.pendingApprovals ?? '—' },
]);

onMounted(loadDashboard);
</script>

<template>
  <div class="p-6">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Welcome, {{ auth.staff.value?.fullName }}</h1>
        <p class="mt-1 text-sm text-gray-600">Role: {{ auth.staff.value?.role }}</p>
      </div>
      <BaseButton :variant="shiftStatus?.onShift ? 'danger' : 'primary'" :disabled="shiftBusy || loading" @click="toggleShift">
        {{ shiftStatus?.onShift ? 'Clock out' : 'Clock in' }}
      </BaseButton>
    </div>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

    <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3">
      <div v-for="tile in statTiles" :key="tile.label" class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-500">{{ tile.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ tile.value }}</p>
      </div>
    </div>

    <p v-if="!shiftStatus?.onShift" class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
      You're not clocked in — most staff actions require an active shift.
    </p>
  </div>
</template>
