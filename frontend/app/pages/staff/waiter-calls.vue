<script setup lang="ts">
import type { WaiterCall } from '~/types/waiterCall';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['handle_waiter_calls'] });

const { t } = useI18n();
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
    error.value = e instanceof Error ? e.message : t('staff.waiterCalls.loadFailed');
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
    error.value = e instanceof Error ? e.message : t('staff.waiterCalls.acceptFailed');
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
    error.value = e instanceof Error ? e.message : t('staff.waiterCalls.completeFailed');
  } finally {
    busyId.value = null;
  }
}

const priorityColor: Record<string, string> = {
  LOW: 'bg-gray-100 text-gray-700',
  NORMAL: 'bg-sky-100 text-sky-800',
  HIGH: 'bg-red-100 text-red-700',
};

function requestTypeLabel(type: string): string {
  return t(`staff.waiterCalls.requestType.${type.toLowerCase()}`);
}

function priorityLabel(priority: string): string {
  return t(`staff.waiterCalls.priority.${priority.toLowerCase()}`);
}

function callStatusLabel(status: string): string {
  return t(`staff.waiterCalls.status.${status.toLowerCase()}`);
}

const liveRefresh = useLiveRefresh('/staff/events', loadCalls, ['waiter_call', 'waiter_call_updated']);
onMounted(() => {
  loadCalls();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.waiterCalls')" :subtitle="$t('staff.pages.callsSub')">
      <template #actions>
        <BaseButton variant="secondary" :disabled="loading" @click="loadCalls">{{ $t('common.refresh') }}</BaseButton>
      </template>
    </StaffPageHeader>

    <p v-if="error" class="staff-error">{{ error }}</p>
    <div v-if="loading" class="staff-empty">{{ $t('staff.waiterCalls.loading') }}</div>
    <div v-else-if="calls.length === 0" class="staff-empty">{{ $t('staff.waiterCalls.empty') }}</div>

    <div v-else class="space-y-3">
      <article v-for="call in calls" :key="call.id" class="staff-card flex flex-wrap items-center justify-between gap-3">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <p class="font-display font-bold text-ink">
              {{ $t('staff.waiterCalls.tableRequest', { number: call.table.tableNumber, type: requestTypeLabel(call.requestType) }) }}
            </p>
            <span class="staff-chip" :class="priorityColor[call.priority]">{{ priorityLabel(call.priority) }}</span>
          </div>
          <p v-if="call.message" class="mt-0.5 text-sm text-ink-muted">{{ call.message }}</p>
          <p class="text-xs text-gray-400">
            {{ callStatusLabel(call.status) }} <span v-if="call.assignedTo">· {{ call.assignedTo.fullName }}</span>
          </p>
        </div>
        <div class="flex gap-2">
          <BaseButton v-if="call.status === 'PENDING'" :disabled="busyId === call.id" @click="accept(call)">{{ $t('staff.waiterCalls.accept') }}</BaseButton>
          <BaseButton v-if="call.status === 'ACKNOWLEDGED'" :disabled="busyId === call.id" @click="complete(call)">{{ $t('staff.waiterCalls.complete') }}</BaseButton>
        </div>
      </article>
    </div>
  </div>
</template>
