<script setup lang="ts">
import type { WaiterCall } from '~/types/waiterCall';

definePageMeta({ middleware: 'staff-auth', layout: 'staff' });

const api = useApi();
const calls = ref<WaiterCall[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const busyId = ref<string | null>(null);

async function loadCalls() {
  loading.value = true;
  error.value = null;
  try {
    calls.value = await api.get<WaiterCall[]>('/staff/waiter-calls');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load waiter calls';
  } finally {
    loading.value = false;
  }
}

async function accept(call: WaiterCall) {
  busyId.value = call.id;
  try {
    await api.post(`/staff/waiter-calls/${call.id}/accept`);
    await loadCalls();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to accept call';
  } finally {
    busyId.value = null;
  }
}

async function complete(call: WaiterCall) {
  busyId.value = call.id;
  try {
    await api.post(`/staff/waiter-calls/${call.id}/complete`);
    await loadCalls();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to complete call';
  } finally {
    busyId.value = null;
  }
}

const priorityColor: Record<string, string> = {
  LOW: 'bg-gray-100 text-gray-700',
  NORMAL: 'bg-blue-100 text-blue-700',
  HIGH: 'bg-red-100 text-red-700',
};

onMounted(loadCalls);
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-gray-900">Waiter calls</h1>
      <BaseButton variant="secondary" :disabled="loading" @click="loadCalls">Refresh</BaseButton>
    </div>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="calls.length === 0" class="text-sm text-gray-500">No waiter calls.</p>

    <div v-else class="space-y-3">
      <div v-for="call in calls" :key="call.id" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4">
        <div>
          <div class="flex items-center gap-2">
            <p class="font-medium text-gray-900">Table {{ call.table.tableNumber }} — {{ call.requestType }}</p>
            <span class="rounded px-2 py-0.5 text-xs font-medium" :class="priorityColor[call.priority]">{{ call.priority }}</span>
          </div>
          <p v-if="call.message" class="text-sm text-gray-500">{{ call.message }}</p>
          <p class="text-xs text-gray-400">{{ call.status }} <span v-if="call.assignedTo">· {{ call.assignedTo.fullName }}</span></p>
        </div>
        <div class="flex gap-2">
          <BaseButton v-if="call.status === 'PENDING'" :disabled="busyId === call.id" @click="accept(call)">Accept</BaseButton>
          <BaseButton v-if="call.status === 'ACKNOWLEDGED'" :disabled="busyId === call.id" @click="complete(call)">Complete</BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
