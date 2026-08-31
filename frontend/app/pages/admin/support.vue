<script setup lang="ts">
import type { SupportMessage, SupportTicket, TicketListResult } from '~/types/support';

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const { t } = useI18n();
const api = useApi();
const tab = ref<'tickets' | 'inquiries'>('tickets');
const tickets = ref<SupportTicket[]>([]);
const messages = ref<SupportMessage[]>([]);
const total = ref(0);
const selected = ref<SupportTicket | null>(null);
const selectedMessage = ref<SupportMessage | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const statusFilter = ref('');
const priorityFilter = ref('');
const search = ref('');

const replyMessage = ref('');
const replying = ref(false);
const updatingStatus = ref(false);

async function loadTickets() {
  loading.value = true;
  error.value = null;
  try {
    const params = new URLSearchParams();
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (priorityFilter.value) params.set('priority', priorityFilter.value);
    if (search.value) params.set('search', search.value);
    const result = await api.get<TicketListResult>(`/admin/support-tickets?${params.toString()}`);
    tickets.value = result.tickets;
    total.value = result.total;
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.support.loadTicketsFailed');
  } finally {
    loading.value = false;
  }
}

async function loadMessages() {
  loading.value = true;
  error.value = null;
  try {
    messages.value = await api.get<SupportMessage[]>('/admin/support-messages');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.support.loadInquiriesFailed');
  } finally {
    loading.value = false;
  }
}

function switchTab(next: 'tickets' | 'inquiries') {
  tab.value = next;
  selected.value = null;
  selectedMessage.value = null;
  if (next === 'tickets') void loadTickets();
  else void loadMessages();
}

async function openTicket(id: string) {
  try {
    selected.value = await api.get<SupportTicket>(`/admin/support-tickets/${id}`);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.support.loadTicketFailed');
  }
}

async function openMessage(message: SupportMessage) {
  selectedMessage.value = message;
  if (message.status === 'NEW') {
    try {
      selectedMessage.value = await api.patch<SupportMessage>(`/admin/support-messages/${message.id}/read`);
      await loadMessages();
    } catch {
      // listing still works even if the mark-read write fails
    }
  }
}

async function archiveMessage() {
  if (!selectedMessage.value) return;
  try {
    await api.patch(`/admin/support-messages/${selectedMessage.value.id}/archive`);
    selectedMessage.value = null;
    await loadMessages();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.support.archiveFailed');
  }
}

async function setStatus(status: string) {
  if (!selected.value) return;
  updatingStatus.value = true;
  try {
    await api.patch(`/admin/support-tickets/${selected.value.id}/status`, { status });
    await openTicket(selected.value.id);
    await loadTickets();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.support.updateTicketFailed');
  } finally {
    updatingStatus.value = false;
  }
}

async function sendReply() {
  if (!selected.value || !replyMessage.value.trim()) return;
  replying.value = true;
  try {
    await api.post(`/admin/support-tickets/${selected.value.id}/replies`, { message: replyMessage.value });
    replyMessage.value = '';
    await openTicket(selected.value.id);
    await loadTickets();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.support.sendReplyFailed');
  } finally {
    replying.value = false;
  }
}

const STATUSES = ['OPEN', 'IN_PROGRESS', 'WAITING_CUSTOMER', 'RESOLVED', 'CLOSED'];
const statusColor: Record<string, string> = {
  OPEN: 'bg-amber-100 text-amber-700',
  IN_PROGRESS: 'bg-blue-100 text-blue-700',
  WAITING_CUSTOMER: 'bg-purple-100 text-purple-700',
  RESOLVED: 'bg-green-100 text-green-700',
  CLOSED: 'bg-gray-100 text-gray-500',
};
const priorityColor: Record<string, string> = {
  LOW: 'text-gray-500',
  MEDIUM: 'text-blue-600',
  HIGH: 'text-orange-600',
  URGENT: 'text-red-600 font-semibold',
};
const statusLabels: Record<string, string> = {
  OPEN: 'admin.support.statusOpen',
  IN_PROGRESS: 'admin.support.statusInProgress',
  WAITING_CUSTOMER: 'admin.support.statusWaitingCustomer',
  RESOLVED: 'admin.support.statusResolved',
  CLOSED: 'admin.support.statusClosed',
};
const priorityLabels: Record<string, string> = {
  LOW: 'admin.support.priorityLow',
  MEDIUM: 'admin.support.priorityMedium',
  HIGH: 'admin.support.priorityHigh',
  URGENT: 'admin.support.priorityUrgent',
};
const messageStatusLabels: Record<string, string> = {
  NEW: 'admin.support.messageStatusNew',
  READ: 'admin.support.messageStatusRead',
  ARCHIVED: 'admin.support.messageStatusArchived',
};
function statusLabel(status: string) {
  return statusLabels[status] ? t(statusLabels[status]) : status.replace('_', ' ');
}
function priorityLabel(priority: string) {
  return priorityLabels[priority] ? t(priorityLabels[priority]) : priority;
}
function messageStatusLabel(status: string) {
  return messageStatusLabels[status] ? t(messageStatusLabels[status]) : status;
}

const liveRefresh = useLiveRefresh('/staff/events', loadTickets, ['support_ticket_created', 'support_ticket_reply']);
onMounted(() => {
  loadTickets();
  liveRefresh.start();
});
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">{{ $t('admin.support.title') }}</h1>

    <div class="mb-4 flex gap-2">
      <button
        class="rounded-full px-3 py-1 text-sm font-semibold"
        :class="tab === 'tickets' ? 'bg-brand text-white' : 'bg-gray-100 text-gray-700'"
        @click="switchTab('tickets')"
      >
        {{ $t('admin.support.tickets') }}
      </button>
      <button
        class="rounded-full px-3 py-1 text-sm font-semibold"
        :class="tab === 'inquiries' ? 'bg-brand text-white' : 'bg-gray-100 text-gray-700'"
        @click="switchTab('inquiries')"
      >
        {{ $t('admin.support.inquiries') }}
      </button>
    </div>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

    <div v-if="tab === 'tickets'">
    <div class="mb-4 flex flex-wrap items-center gap-2">
      <select v-model="statusFilter" class="rounded-md border border-gray-300 px-2 py-1 text-sm" @change="loadTickets">
        <option value="">{{ $t('admin.support.allStatuses') }}</option>
        <option v-for="s in STATUSES" :key="s" :value="s">{{ statusLabel(s) }}</option>
      </select>
      <select v-model="priorityFilter" class="rounded-md border border-gray-300 px-2 py-1 text-sm" @change="loadTickets">
        <option value="">{{ $t('admin.support.allPriorities') }}</option>
        <option value="URGENT">{{ $t('admin.support.priorityUrgent') }}</option>
        <option value="HIGH">{{ $t('admin.support.priorityHigh') }}</option>
        <option value="MEDIUM">{{ $t('admin.support.priorityMedium') }}</option>
        <option value="LOW">{{ $t('admin.support.priorityLow') }}</option>
      </select>
      <input v-model="search" type="text" :placeholder="$t('admin.support.searchPlaceholder')" class="rounded-md border border-gray-300 px-2 py-1 text-sm" @keyup.enter="loadTickets" />
      <BaseButton variant="secondary" :disabled="loading" @click="loadTickets">{{ $t('common.search') }}</BaseButton>
      <span class="text-xs text-gray-400">{{ $t('admin.support.ticketCount', { count: total }) }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <div>
        <p v-if="loading" class="text-sm text-gray-500">{{ $t('common.loading') }}</p>
        <p v-else-if="tickets.length === 0" class="text-sm text-gray-500">{{ $t('admin.support.noTickets') }}</p>
        <div class="space-y-2">
          <button
            v-for="t in tickets"
            :key="t.id"
            class="block w-full rounded-lg border p-3 text-left"
            :class="selected?.id === t.id ? 'border-blue-400 bg-blue-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
            @click="openTicket(t.id)"
          >
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-900">{{ t.subject }}</p>
              <span class="rounded px-2 py-0.5 text-xs font-medium" :class="statusColor[t.status]">{{ statusLabel(t.status) }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-500">
              {{ t.restaurant?.name ?? $t('admin.support.unknownRestaurant') }} · {{ t.staff?.fullName ?? $t('admin.support.unknownStaff') }} ·
              <span :class="priorityColor[t.priority]">{{ priorityLabel(t.priority) }}</span>
            </p>
          </button>
        </div>
      </div>

      <div v-if="selected" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-sm font-semibold text-gray-900">{{ selected.subject }}</h2>
            <p class="text-xs text-gray-500">{{ selected.restaurant?.name }} · {{ selected.staff?.fullName }}</p>
          </div>
          <select :value="selected.status" class="rounded-md border border-gray-300 px-2 py-1 text-xs" :disabled="updatingStatus" @change="setStatus(($event.target as HTMLSelectElement).value)">
            <option v-for="s in STATUSES" :key="s" :value="s">{{ statusLabel(s) }}</option>
          </select>
        </div>
        <p v-if="selected.description" class="mt-2 text-sm text-gray-600">{{ selected.description }}</p>

        <div class="mt-4 max-h-80 space-y-3 overflow-y-auto border-t border-gray-100 pt-3">
          <div v-for="r in selected.replies" :key="r.id" class="text-sm">
            <p class="text-xs font-medium" :class="r.isSuperadmin ? 'text-blue-600' : 'text-gray-700'">
              {{ r.isSuperadmin ? $t('admin.support.youSupport') : (r.staff?.fullName ?? $t('admin.support.unknownRestaurant')) }} · {{ new Date(r.createdAt).toLocaleString() }}
            </p>
            <p class="mt-0.5 text-gray-800">{{ r.message }}</p>
          </div>
          <p v-if="!selected.replies?.length" class="text-xs text-gray-400">{{ $t('admin.support.noReplies') }}</p>
        </div>

        <div class="mt-4 flex gap-2 border-t border-gray-100 pt-3">
          <input v-model="replyMessage" type="text" class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm" :placeholder="$t('admin.support.replyPlaceholder')" @keyup.enter="sendReply" />
          <BaseButton :disabled="replying || !replyMessage.trim()" @click="sendReply">{{ replying ? $t('admin.support.sending') : $t('admin.support.send') }}</BaseButton>
        </div>
      </div>
    </div>
    </div>

    <div v-else class="grid gap-6 lg:grid-cols-2">
      <div>
        <p v-if="loading" class="text-sm text-gray-500">{{ $t('common.loading') }}</p>
        <p v-else-if="messages.length === 0" class="text-sm text-gray-500">{{ $t('admin.support.noInquiries') }}</p>
        <div class="space-y-2">
          <button
            v-for="m in messages"
            :key="m.id"
            class="block w-full rounded-lg border p-3 text-left"
            :class="selectedMessage?.id === m.id ? 'border-blue-400 bg-blue-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
            @click="openMessage(m)"
          >
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-900">{{ m.subject }}</p>
              <span class="rounded px-2 py-0.5 text-xs font-medium" :class="m.status === 'NEW' ? 'bg-amber-100 text-amber-700' : m.status === 'READ' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'">{{ messageStatusLabel(m.status) }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-500">{{ m.contactName ?? $t('admin.support.anonymous') }} · {{ m.contactEmail }} · {{ m.restaurant?.name ?? $t('admin.support.noRestaurant') }}</p>
          </button>
        </div>
      </div>
      <div v-if="selectedMessage" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-sm font-semibold text-gray-900">{{ selectedMessage.subject }}</h2>
            <p class="text-xs text-gray-500">{{ selectedMessage.contactName }} · {{ selectedMessage.contactEmail }}</p>
          </div>
          <BaseButton variant="secondary" @click="archiveMessage">{{ $t('admin.support.archive') }}</BaseButton>
        </div>
        <p class="mt-3 whitespace-pre-wrap text-sm text-gray-700">{{ selectedMessage.message }}</p>
      </div>
    </div>
  </div>
</template>
