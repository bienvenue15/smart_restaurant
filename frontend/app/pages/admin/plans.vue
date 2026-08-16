<script setup lang="ts">
import type { AdminSubscriptionPlan } from '~/types/admin';

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const api = useApi();
const plans = ref<AdminSubscriptionPlan[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

async function loadPlans() {
  loading.value = true;
  error.value = null;
  try {
    plans.value = await api.get<AdminSubscriptionPlan[]>('/admin/plans');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load plans';
  } finally {
    loading.value = false;
  }
}

async function toggleActive(plan: AdminSubscriptionPlan) {
  try {
    await api.patch(`/admin/plans/${plan.planName}`, { isActive: !plan.isActive });
    await loadPlans();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to update plan';
  }
}

onMounted(loadPlans);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Subscription plans</h1>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="plan in plans" :key="plan.id" class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="font-semibold text-gray-900">{{ plan.displayName }}</p>
        <p class="text-sm text-gray-500">{{ Number(plan.priceMonthly).toLocaleString() }} / month</p>
        <p class="mt-2 text-xs text-gray-400">{{ plan.maxTables }} tables · {{ plan.maxUsers }} users</p>
        <BaseButton class="mt-3" variant="secondary" @click="toggleActive(plan)">
          {{ plan.isActive ? 'Deactivate' : 'Activate' }}
        </BaseButton>
      </div>
    </div>
  </div>
</template>
