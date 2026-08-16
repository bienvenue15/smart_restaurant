<script setup lang="ts">
import type { PlatformStats } from '~/types/admin';

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const api = useApi();
const stats = ref<PlatformStats | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

async function loadStats() {
  loading.value = true;
  error.value = null;
  try {
    stats.value = await api.get<PlatformStats>('/admin/stats');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load platform stats';
  } finally {
    loading.value = false;
  }
}

const tiles = computed(() => [
  { label: 'Total restaurants', value: stats.value?.totalRestaurants ?? '—' },
  { label: 'Active restaurants', value: stats.value?.activeRestaurants ?? '—' },
  { label: 'Total orders (all time)', value: stats.value?.totalOrders ?? '—' },
  { label: "Today's orders", value: stats.value?.todayOrders ?? '—' },
  { label: 'This month\'s revenue', value: stats.value ? stats.value.monthRevenue.toLocaleString() : '—' },
]);

onMounted(loadStats);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Platform overview</h1>
    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

    <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3">
      <div v-for="tile in tiles" :key="tile.label" class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-500">{{ tile.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ tile.value }}</p>
      </div>
    </div>
  </div>
</template>
