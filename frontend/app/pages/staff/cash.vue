<script setup lang="ts">
import type { CashSession } from '~/types/cash';

definePageMeta({ middleware: 'staff-auth', layout: 'staff' });

const api = useApi();
const session = ref<CashSession | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const openingBalance = ref<number | null>(null);
const closingBalance = ref<number | null>(null);
const busy = ref(false);

async function loadSession() {
  loading.value = true;
  error.value = null;
  try {
    session.value = await api.get<CashSession | null>('/staff/cash/sessions/current');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load cash session';
  } finally {
    loading.value = false;
  }
}

async function openSession() {
  if (openingBalance.value == null) return;
  busy.value = true;
  try {
    await api.post('/staff/cash/sessions', { openingBalance: openingBalance.value });
    openingBalance.value = null;
    await loadSession();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to open session';
  } finally {
    busy.value = false;
  }
}

async function closeSession() {
  if (!session.value || closingBalance.value == null) return;
  busy.value = true;
  try {
    await api.post(`/staff/cash/sessions/${session.value.id}/close`, { closingBalance: closingBalance.value });
    closingBalance.value = null;
    await loadSession();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to close session';
  } finally {
    busy.value = false;
  }
}

onMounted(loadSession);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Cash session</h1>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

    <template v-else-if="session">
      <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-500">Opening balance</p>
        <p class="text-lg font-semibold text-gray-900">{{ Number(session.openingBalance).toLocaleString() }}</p>
        <p class="mt-2 text-sm text-gray-500">Cash in hand</p>
        <p class="text-lg font-semibold text-gray-900">{{ Number(session.cashInHand).toLocaleString() }}</p>
      </div>

      <form class="flex items-end gap-2" @submit.prevent="closeSession">
        <div>
          <label class="block text-xs text-gray-500">Closing balance (counted cash)</label>
          <input v-model.number="closingBalance" type="number" step="0.01" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
        </div>
        <BaseButton type="submit" variant="danger" :disabled="busy">Close session</BaseButton>
      </form>

      <h2 class="mb-2 mt-6 text-sm font-semibold text-gray-900">Transactions</h2>
      <ul class="space-y-1 text-sm text-gray-700">
        <li v-for="t in session.transactions" :key="t.id">{{ t.transactionType }} — {{ Number(t.amount).toLocaleString() }}</li>
        <li v-if="session.transactions.length === 0" class="text-gray-400">No transactions yet.</li>
      </ul>
    </template>

    <form v-else class="flex items-end gap-2" @submit.prevent="openSession">
      <div>
        <label class="block text-xs text-gray-500">Opening balance</label>
        <input v-model.number="openingBalance" type="number" step="0.01" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <BaseButton type="submit" :disabled="busy">Open cash session</BaseButton>
    </form>
  </div>
</template>
