<script setup lang="ts">
import type { AdminSubscriptionPlan } from '~/types/admin';
import { ENFORCEABLE_PLAN_FEATURES } from '~/composables/usePlan';

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const { t } = useI18n();
const api = useApi();
const plans = ref<AdminSubscriptionPlan[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const editingPlan = ref<string | null>(null);
const saving = ref(false);

const form = ref({
  displayName: '',
  priceMonthly: 0,
  priceYearly: 0,
  maxTables: 0,
  maxUsers: 0,
  maxMenuItems: 0,
  maxOrdersPerMonth: 0,
  features: [] as string[],
});

async function loadPlans() {
  loading.value = true;
  error.value = null;
  try {
    plans.value = await api.get<AdminSubscriptionPlan[]>('/admin/plans');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.plans.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function toggleActive(plan: AdminSubscriptionPlan) {
  try {
    await api.patch(`/admin/plans/${plan.planName}`, { isActive: !plan.isActive });
    await loadPlans();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.plans.updateFailed');
  }
}

function startEdit(plan: AdminSubscriptionPlan) {
  editingPlan.value = plan.planName;
  form.value = {
    displayName: plan.displayName,
    priceMonthly: Number(plan.priceMonthly),
    priceYearly: Number(plan.priceYearly),
    maxTables: plan.maxTables,
    maxUsers: plan.maxUsers,
    maxMenuItems: plan.maxMenuItems,
    maxOrdersPerMonth: plan.maxOrdersPerMonth,
    features: ENFORCEABLE_PLAN_FEATURES.filter((code) => (plan.features ?? []).includes(code)),
  };
}

async function saveEdit(plan: AdminSubscriptionPlan) {
  saving.value = true;
  error.value = null;
  try {
    const extras = (plan.features ?? []).filter((code) => !ENFORCEABLE_PLAN_FEATURES.includes(code as (typeof ENFORCEABLE_PLAN_FEATURES)[number]));
    await api.patch(`/admin/plans/${plan.planName}`, {
      ...form.value,
      features: [...form.value.features, ...extras],
    });
    editingPlan.value = null;
    await loadPlans();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.plans.saveFailed');
  } finally {
    saving.value = false;
  }
}

onMounted(loadPlans);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">{{ $t('admin.plans.title') }}</h1>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">{{ $t('common.loading') }}</p>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="plan in plans" :key="plan.id" class="rounded-lg border border-gray-200 bg-white p-4">
        <template v-if="editingPlan === plan.planName">
          <div class="space-y-2">
            <div>
              <label class="mb-0.5 block text-[11px] font-medium text-gray-700">{{ $t('admin.plans.displayName') }}</label>
              <input v-model="form.displayName" type="text" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" />
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="mb-0.5 block text-[11px] font-medium text-gray-700">{{ $t('admin.plans.monthly') }}</label>
                <input v-model.number="form.priceMonthly" type="number" min="0" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" />
              </div>
              <div>
                <label class="mb-0.5 block text-[11px] font-medium text-gray-700">{{ $t('admin.plans.yearly') }}</label>
                <input v-model.number="form.priceYearly" type="number" min="0" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="mb-0.5 block text-[11px] font-medium text-gray-700">{{ $t('admin.plans.maxTables') }}</label>
                <input v-model.number="form.maxTables" type="number" min="0" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" />
              </div>
              <div>
                <label class="mb-0.5 block text-[11px] font-medium text-gray-700">{{ $t('admin.plans.maxUsers') }}</label>
                <input v-model.number="form.maxUsers" type="number" min="0" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" />
              </div>
              <div>
                <label class="mb-0.5 block text-[11px] font-medium text-gray-700">{{ $t('admin.plans.maxMenuItems') }}</label>
                <input v-model.number="form.maxMenuItems" type="number" min="0" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" />
              </div>
              <div>
                <label class="mb-0.5 block text-[11px] font-medium text-gray-700">{{ $t('admin.plans.maxOrdersPerMonth') }}</label>
                <input v-model.number="form.maxOrdersPerMonth" type="number" min="0" class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs" />
              </div>
            </div>
            <div>
              <p class="mb-1 text-[11px] font-medium text-gray-700">{{ $t('admin.plans.enforcedFeatures') }}</p>
              <p class="mb-2 text-[11px] text-gray-400">{{ $t('admin.plans.featureHint') }}</p>
              <label v-for="code in ENFORCEABLE_PLAN_FEATURES" :key="code" class="mb-1 flex items-center gap-2 text-xs text-gray-700">
                <input v-model="form.features" type="checkbox" :value="code" />
                {{ $t(`admin.plans.feature.${code}`) }}
              </label>
            </div>
            <div class="flex gap-2">
              <button
                class="rounded-md bg-blue-600 px-2 py-1 text-xs font-medium text-white disabled:opacity-50"
                :disabled="saving"
                @click="saveEdit(plan)"
              >
                {{ saving ? $t('admin.plans.saving') : $t('common.save') }}
              </button>
              <button class="text-xs text-gray-500 hover:text-gray-700" @click="editingPlan = null">{{ $t('common.cancel') }}</button>
            </div>
          </div>
        </template>

        <template v-else>
          <p class="font-semibold text-gray-900">{{ plan.displayName }}</p>
          <p class="text-sm text-gray-500">{{ $t('admin.plans.perMonth', { price: Number(plan.priceMonthly).toLocaleString() }) }}</p>
          <p class="mt-2 text-xs text-gray-400">{{ $t('admin.plans.limitsSummary', { tables: plan.maxTables, users: plan.maxUsers, items: plan.maxMenuItems, orders: plan.maxOrdersPerMonth }) }}</p>
          <ul v-if="plan.features?.length" class="mt-2 space-y-0.5 text-xs text-gray-500">
            <li v-for="f in plan.features" :key="f">• {{ f }}</li>
          </ul>
          <div class="mt-3 flex gap-2">
            <BaseButton variant="secondary" @click="startEdit(plan)">{{ $t('common.edit') }}</BaseButton>
            <BaseButton variant="secondary" @click="toggleActive(plan)">
              {{ plan.isActive ? $t('admin.plans.deactivate') : $t('admin.plans.activate') }}
            </BaseButton>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
