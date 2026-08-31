<script setup lang="ts">
import type { Announcement } from '~/types/announcement';

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const { t } = useI18n();
const api = useApi();
const announcements = ref<Announcement[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const submitting = ref(false);
const showForm = ref(false);

const form = ref({
  title: '',
  message: '',
  type: 'INFO' as Announcement['type'],
  targetAudience: 'ALL' as Announcement['targetAudience'],
  priority: 'NORMAL' as Announcement['priority'],
});

async function loadAnnouncements() {
  loading.value = true;
  error.value = null;
  try {
    announcements.value = await api.get<Announcement[]>('/admin/announcements');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.announcements.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function createAnnouncement() {
  if (!form.value.title.trim() || !form.value.message.trim()) return;
  submitting.value = true;
  try {
    await api.post('/admin/announcements', form.value);
    form.value = { title: '', message: '', type: 'INFO', targetAudience: 'ALL', priority: 'NORMAL' };
    showForm.value = false;
    await loadAnnouncements();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.announcements.createFailed');
  } finally {
    submitting.value = false;
  }
}

async function toggle(a: Announcement) {
  try {
    await api.post(`/admin/announcements/${a.id}/toggle`);
    await loadAnnouncements();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.announcements.updateFailed');
  }
}

async function remove(a: Announcement) {
  try {
    await api.del(`/admin/announcements/${a.id}`);
    await loadAnnouncements();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.announcements.deleteFailed');
  }
}

const typeColor: Record<string, string> = {
  INFO: 'bg-blue-100 text-blue-700',
  WARNING: 'bg-amber-100 text-amber-700',
  SUCCESS: 'bg-green-100 text-green-700',
  DANGER: 'bg-red-100 text-red-700',
  PROMOTION: 'bg-purple-100 text-purple-700',
};

const typeLabels: Record<string, string> = {
  INFO: 'admin.announcements.typeInfo',
  WARNING: 'admin.announcements.typeWarning',
  SUCCESS: 'admin.announcements.typeSuccess',
  DANGER: 'admin.announcements.typeDanger',
  PROMOTION: 'admin.announcements.typePromotion',
};
const audienceLabels: Record<string, string> = {
  ALL: 'admin.announcements.audienceAll',
  STAFF: 'admin.announcements.audienceStaff',
  ADMINS: 'admin.announcements.audienceAdmins',
  CUSTOMERS: 'admin.announcements.audienceCustomers',
};
const priorityLabels: Record<string, string> = {
  LOW: 'admin.announcements.priorityLow',
  NORMAL: 'admin.announcements.priorityNormal',
  HIGH: 'admin.announcements.priorityHigh',
  URGENT: 'admin.announcements.priorityUrgent',
};
function typeLabel(type: string) {
  return typeLabels[type] ? t(typeLabels[type]) : type;
}
function audienceLabel(audience: string) {
  return audienceLabels[audience] ? t(audienceLabels[audience]) : audience;
}
function priorityLabel(priority: string) {
  return priorityLabels[priority] ? t(priorityLabels[priority]) : priority;
}

const liveRefresh = useLiveRefresh('/staff/events', loadAnnouncements, ['announcement_updated']);
onMounted(() => {
  loadAnnouncements();
  liveRefresh.start();
});
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-gray-900">{{ $t('admin.nav.announcements') }}</h1>
      <BaseButton @click="showForm = !showForm">{{ showForm ? $t('common.cancel') : $t('admin.announcements.new') }}</BaseButton>
    </div>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

    <div v-if="showForm" class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
      <div class="mb-3">
        <label class="mb-1 block text-xs font-medium text-gray-700">{{ $t('admin.announcements.titleLabel') }}</label>
        <input v-model="form.title" type="text" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="mb-3">
        <label class="mb-1 block text-xs font-medium text-gray-700">{{ $t('admin.announcements.messageLabel') }}</label>
        <textarea v-model="form.message" rows="3" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="mb-3 grid grid-cols-3 gap-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700">{{ $t('admin.announcements.typeLabel') }}</label>
          <select v-model="form.type" class="w-full rounded-md border border-gray-300 px-2 py-2 text-sm">
            <option value="INFO">{{ $t('admin.announcements.typeInfo') }}</option>
            <option value="WARNING">{{ $t('admin.announcements.typeWarning') }}</option>
            <option value="SUCCESS">{{ $t('admin.announcements.typeSuccess') }}</option>
            <option value="DANGER">{{ $t('admin.announcements.typeDanger') }}</option>
            <option value="PROMOTION">{{ $t('admin.announcements.typePromotion') }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700">{{ $t('admin.announcements.audienceLabel') }}</label>
          <select v-model="form.targetAudience" class="w-full rounded-md border border-gray-300 px-2 py-2 text-sm">
            <option value="ALL">{{ $t('admin.announcements.audienceAll') }}</option>
            <option value="STAFF">{{ $t('admin.announcements.audienceStaff') }}</option>
            <option value="ADMINS">{{ $t('admin.announcements.audienceAdmins') }}</option>
            <option value="CUSTOMERS">{{ $t('admin.announcements.audienceCustomers') }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700">{{ $t('admin.announcements.priorityLabel') }}</label>
          <select v-model="form.priority" class="w-full rounded-md border border-gray-300 px-2 py-2 text-sm">
            <option value="LOW">{{ $t('admin.announcements.priorityLow') }}</option>
            <option value="NORMAL">{{ $t('admin.announcements.priorityNormal') }}</option>
            <option value="HIGH">{{ $t('admin.announcements.priorityHigh') }}</option>
            <option value="URGENT">{{ $t('admin.announcements.priorityUrgent') }}</option>
          </select>
        </div>
      </div>
      <BaseButton :disabled="submitting || !form.title.trim() || !form.message.trim()" @click="createAnnouncement">
        {{ submitting ? $t('admin.announcements.publishing') : $t('admin.announcements.publish') }}
      </BaseButton>
    </div>

    <p v-if="loading" class="text-sm text-gray-500">{{ $t('common.loading') }}</p>
    <p v-else-if="announcements.length === 0" class="text-sm text-gray-500">{{ $t('admin.announcements.empty') }}</p>

    <div class="space-y-3">
      <div v-for="a in announcements" :key="a.id" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="mb-1 flex items-center gap-2">
              <p class="font-medium text-gray-900">{{ a.title }}</p>
              <span class="rounded px-2 py-0.5 text-xs font-medium" :class="typeColor[a.type]">{{ typeLabel(a.type) }}</span>
              <span v-if="!a.isActive" class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ $t('common.inactive') }}</span>
            </div>
            <p class="text-sm text-gray-600">{{ a.message }}</p>
            <p class="mt-1 text-xs text-gray-400">
              {{ audienceLabel(a.targetAudience) }} · {{ priorityLabel(a.priority) }} · {{ a.restaurant?.name ?? $t('admin.announcements.allRestaurants') }} · {{ $t('admin.announcements.dismissedCount', { count: a._count?.dismissals ?? 0 }) }}
            </p>
          </div>
          <div class="flex shrink-0 gap-2">
            <button class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50" @click="toggle(a)">
              {{ a.isActive ? $t('admin.announcements.deactivate') : $t('admin.announcements.activate') }}
            </button>
            <button class="rounded-md border border-red-300 px-2 py-1 text-xs text-red-700 hover:bg-red-50" @click="remove(a)">{{ $t('common.delete') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
