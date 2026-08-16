<script setup lang="ts">
import type { RestaurantTable } from '~/types/table';

definePageMeta({ middleware: 'staff-auth', layout: 'staff' });

const api = useApi();
const tables = ref<RestaurantTable[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creating = ref(false);
const newTableNumber = ref('');
const newSeats = ref(4);

async function loadTables() {
  loading.value = true;
  error.value = null;
  try {
    tables.value = await api.get<RestaurantTable[]>('/staff/tables');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load tables';
  } finally {
    loading.value = false;
  }
}

async function createTable() {
  if (!newTableNumber.value.trim()) return;
  creating.value = true;
  try {
    await api.post('/staff/tables', { tableNumber: newTableNumber.value, seats: newSeats.value });
    newTableNumber.value = '';
    newSeats.value = 4;
    await loadTables();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to create table';
  } finally {
    creating.value = false;
  }
}

async function resetTable(table: RestaurantTable) {
  try {
    await api.post(`/staff/tables/${table.id}/reset`);
    await loadTables();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to reset table';
  }
}

async function removeTable(table: RestaurantTable) {
  try {
    await api.del(`/staff/tables/${table.id}`);
    await loadTables();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to delete table';
  }
}

function menuUrl(table: RestaurantTable): string {
  return `${window.location.origin}/menu/${table.qrCode}`;
}

async function copyLink(table: RestaurantTable) {
  await navigator.clipboard.writeText(menuUrl(table));
}

const statusColor: Record<string, string> = {
  AVAILABLE: 'bg-green-100 text-green-700',
  OCCUPIED: 'bg-amber-100 text-amber-700',
  RESERVED: 'bg-blue-100 text-blue-700',
  CLEANING: 'bg-gray-100 text-gray-700',
};

onMounted(loadTables);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Tables</h1>

    <form class="mb-6 flex items-end gap-2" @submit.prevent="createTable">
      <div>
        <label class="block text-xs text-gray-500">Table number</label>
        <input v-model="newTableNumber" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" placeholder="T1" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">Seats</label>
        <input v-model.number="newSeats" type="number" min="1" class="w-20 rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <BaseButton type="submit" :disabled="creating">Add table</BaseButton>
    </form>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="table in tables" :key="table.id" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="mb-2 flex items-center justify-between">
          <p class="font-medium text-gray-900">Table {{ table.tableNumber }}</p>
          <span class="rounded px-2 py-0.5 text-xs font-medium" :class="statusColor[table.status]">{{ table.status }}</span>
        </div>
        <p class="text-sm text-gray-500">{{ table.seats }} seats</p>

        <div class="mt-3 flex flex-wrap gap-2">
          <BaseButton variant="secondary" @click="copyLink(table)">Copy menu link</BaseButton>
          <BaseButton variant="secondary" @click="resetTable(table)">Reset</BaseButton>
          <BaseButton variant="danger" @click="removeTable(table)">Delete</BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
