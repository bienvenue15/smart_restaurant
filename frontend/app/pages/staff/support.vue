<script setup lang="ts">
import type { SupportTicket } from '~/types/support';

definePageMeta({ middleware: 'staff-auth', layout: 'staff' });

const { t } = useI18n();
const api = useApi();
const tickets = ref<SupportTicket[]>([]);
const selected = ref<SupportTicket | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const showNewForm = ref(false);
const newSubject = ref('');
const newDescription = ref('');
const newPriority = ref<'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT'>('MEDIUM');
const submitting = ref(false);

const replyMessage = ref('');
const replying = ref(false);

async function loadTickets() {
  loading.value = true;
  error.value = null;
  try {
    tickets.value = await api.get<SupportTicket[]>('/staff/support-tickets');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.support.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function openTicket(id: string) {
  try {
    selected.value = await api.get<SupportTicket>(`/staff/support-tickets/${id}`);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.support.loadTicketFailed');
  }
}

async function submitTicket() {
  if (!newSubject.value.trim()) return;
  submitting.value = true;
  try {
    await api.post('/staff/support-tickets', { subject: newSubject.value, description: newDescription.value, priority: newPriority.value });
    newSubject.value = '';
    newDescription.value = '';
    newPriority.value = 'MEDIUM';
    showNewForm.value = false;
    await loadTickets();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.support.submitTicketFailed');
  } finally {
    submitting.value = false;
  }
}

async function sendReply() {
  if (!selected.value || !replyMessage.value.trim()) return;
  replying.value = true;
  try {
    await api.post(`/staff/support-tickets/${selected.value.id}/replies`, { message: replyMessage.value });
    replyMessage.value = '';
    await openTicket(selected.value.id);
    await loadTickets();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.support.sendReplyFailed');
  } finally {
    replying.value = false;
  }
}

const statusColor: Record<string, string> = {
  OPEN: 'bg-amber-100 text-amber-900',
  IN_PROGRESS: 'bg-sky-100 text-sky-800',
  WAITING_CUSTOMER: 'bg-violet-100 text-violet-800',
  RESOLVED: 'bg-emerald-100 text-emerald-800',
  CLOSED: 'bg-gray-100 text-gray-500',
};

function ticketStatusLabel(status: string): string {
  return t(`staff.support.status.${status.toLowerCase()}`);
}

const liveRefresh = useLiveRefresh('/staff/events', loadTickets, ['support_ticket_status', 'support_ticket_reply']);
onMounted(() => {
  loadTickets();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.support')" :subtitle="$t('staff.pages.supportSub')">
      <template #actions>
        <BaseButton @click="showNewForm = !showNewForm">{{ showNewForm ? $t('common.cancel') : $t('staff.support.newTicket') }}</BaseButton>
      </template>
    </StaffPageHeader>

    <p v-if="error" class="staff-error">{{ error }}</p>

    <div v-if="showNewForm" class="staff-card mb-5 max-w-xl space-y-3">
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.support.subjectLabel') }}</label>
        <input v-model="newSubject" type="text" class="staff-input" :placeholder="$t('staff.support.subjectPlaceholder')" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.support.descriptionLabel') }}</label>
        <textarea v-model="newDescription" rows="3" class="staff-input" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.support.priorityLabel') }}</label>
        <select v-model="newPriority" class="staff-select">
          <option value="LOW">{{ $t('staff.support.priority.low') }}</option>
          <option value="MEDIUM">{{ $t('staff.support.priority.medium') }}</option>
          <option value="HIGH">{{ $t('staff.support.priority.high') }}</option>
          <option value="URGENT">{{ $t('staff.support.priority.urgent') }}</option>
        </select>
      </div>
      <BaseButton :disabled="submitting || !newSubject.trim()" @click="submitTicket">
        {{ submitting ? $t('staff.support.submitting') : $t('staff.support.submitTicket') }}
      </BaseButton>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
      <div>
        <div v-if="loading" class="staff-empty">{{ $t('staff.support.loadingTickets') }}</div>
        <div v-else-if="tickets.length === 0" class="staff-empty">{{ $t('staff.support.noTickets') }}</div>
        <div v-else class="space-y-2">
          <button
            v-for="t in tickets"
            :key="t.id"
            type="button"
            class="block w-full rounded-2xl border p-3 text-left transition"
            :class="selected?.id === t.id ? 'border-brand bg-brand/5 shadow-sm' : 'border-[var(--line)] bg-white hover:bg-surface'"
            @click="openTicket(t.id)"
          >
            <div class="flex items-center justify-between gap-2">
              <p class="font-display text-sm font-bold text-ink">{{ t.subject }}</p>
              <span class="staff-chip" :class="statusColor[t.status]">{{ ticketStatusLabel(t.status) }}</span>
            </div>
            <p class="mt-1 text-xs text-ink-muted">
              {{ $t('staff.support.repliesUpdated', { count: t._count?.replies ?? 0, date: new Date(t.updatedAt).toLocaleDateString() }) }}
            </p>
          </button>
        </div>
      </div>

      <div v-if="selected" class="staff-card">
        <h2 class="font-display font-bold text-ink">{{ selected.subject }}</h2>
        <p v-if="selected.description" class="mt-1 text-sm text-ink-muted">{{ selected.description }}</p>

        <div class="mt-4 max-h-80 space-y-3 overflow-y-auto border-t border-gray-100 pt-3">
          <div v-for="r in selected.replies" :key="r.id" class="text-sm">
            <p class="text-xs font-semibold" :class="r.isSuperadmin ? 'text-brand' : 'text-ink'">
              {{ r.isSuperadmin ? $t('staff.support.supportLabel') : (r.staff?.fullName ?? $t('staff.support.youLabel')) }} · {{ new Date(r.createdAt).toLocaleString() }}
            </p>
            <p class="mt-0.5 text-ink">{{ r.message }}</p>
          </div>
          <p v-if="!selected.replies?.length" class="text-xs text-ink-muted">{{ $t('staff.support.noReplies') }}</p>
        </div>

        <div class="mt-4 flex gap-2 border-t border-gray-100 pt-3">
          <input
            v-model="replyMessage"
            type="text"
            class="staff-input flex-1"
            :placeholder="$t('staff.support.replyPlaceholder')"
            @keyup.enter="sendReply"
          />
          <BaseButton :disabled="replying || !replyMessage.trim()" @click="sendReply">
            {{ replying ? $t('staff.support.sending') : $t('staff.support.send') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
