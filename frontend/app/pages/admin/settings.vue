<script setup lang="ts">
import type { BackupList } from '~/types/admin';

interface SystemSetting {
  id: string;
  settingKey: string;
  settingValue: string | null;
  description: string | null;
  updatedAt: string;
}

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const { t } = useI18n();
const api = useApi();
const runtimeConfig = useRuntimeConfig();
const auth = useAuthStore();
const settings = ref<SystemSetting[]>([]);
const backups = ref<BackupList | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const saving = ref<string | null>(null);

const maintenanceOn = computed(() => settings.value.find((s) => s.settingKey === 'maintenance_mode')?.settingValue?.toLowerCase() === 'on');

const businessHoursDraft = ref('');
const scheduleDraft = ref('02:00 Africa/Kigali');
const retentionDraft = ref(30);
const DEFAULT_HOURS: Record<string, string> = { mon: '09:00-22:00', tue: '09:00-22:00', wed: '09:00-22:00', thu: '09:00-22:00', fri: '09:00-22:00', sat: '09:00-23:00', sun: '10:00-21:00' };

function formatBytes(bytes: number) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const [loadedSettings, loadedBackups] = await Promise.all([
      api.get<SystemSetting[]>('/admin/settings'),
      api.get<BackupList>('/admin/backups'),
    ]);
    settings.value = loadedSettings;
    backups.value = loadedBackups;
    const hours = loadedSettings.find((s) => s.settingKey === 'business_hours')?.settingValue;
    businessHoursDraft.value = hours ?? JSON.stringify(DEFAULT_HOURS, null, 2);
    scheduleDraft.value = loadedBackups.schedule;
    retentionDraft.value = loadedBackups.retentionDays;
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.settings.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function toggleMaintenance() {
  saving.value = 'maintenance_mode';
  try {
    await api.patch('/admin/settings/maintenance_mode', {
      value: maintenanceOn.value ? 'off' : 'on',
      description: 'When "on", the API rejects all customer and non-superadmin staff requests with 503.',
    });
    await load();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.settings.maintenanceUpdateFailed');
  } finally {
    saving.value = null;
  }
}

async function saveBusinessHours() {
  saving.value = 'business_hours';
  error.value = null;
  try {
    JSON.parse(businessHoursDraft.value);
    await api.patch('/admin/settings/business_hours', {
      value: businessHoursDraft.value,
      description: 'Per-day open-close ranges, e.g. {"mon":"09:00-22:00"}.',
    });
    await load();
  } catch (e) {
    error.value = e instanceof Error ? (e.message.includes('JSON') ? t('admin.settings.hoursInvalidJson') : e.message) : t('admin.settings.hoursSaveFailed');
  } finally {
    saving.value = null;
  }
}

async function saveBackupSettings() {
  saving.value = 'backup_settings';
  error.value = null;
  try {
    await Promise.all([
      api.patch('/admin/settings/backup_schedule', {
        value: scheduleDraft.value,
        description: 'Informational backup schedule label for operators.',
      }),
      api.patch('/admin/settings/backup_retention_days', {
        value: String(retentionDraft.value),
        description: 'Delete dumps older than this many days; also keep at most 10 files.',
      }),
    ]);
    await load();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.settings.backupSettingsSaveFailed');
  } finally {
    saving.value = null;
  }
}

async function triggerBackup() {
  saving.value = 'backup';
  error.value = null;
  try {
    await api.post('/admin/backups');
    await load();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.settings.triggerBackupFailed');
  } finally {
    saving.value = null;
  }
}

async function downloadBackup(filename: string) {
  error.value = null;
  try {
    const response = await fetch(`${runtimeConfig.public.apiBaseUrl}/admin/backups/${encodeURIComponent(filename)}`, {
      headers: auth.accessToken.value ? { Authorization: `Bearer ${auth.accessToken.value}` } : undefined,
      credentials: 'include',
    });
    if (!response.ok) throw new Error(t('admin.settings.downloadFailed'));
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
  } catch (e) {
    error.value =
      e instanceof TypeError
        ? t('common.offline')
        : e instanceof Error
          ? e.message
          : t('admin.settings.downloadFailed');
  }
}

onMounted(load);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">{{ $t('admin.settings.title') }}</h1>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">{{ $t('common.loading') }}</p>

    <div v-else class="max-w-xl space-y-6">
      <ChangePasswordForm />

      <TwoFactorForm />

      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">{{ $t('admin.settings.maintenanceMode') }}</p>
            <p class="text-xs text-gray-500">{{ $t('admin.settings.maintenanceHint') }}</p>
          </div>
          <button
            class="rounded-full px-4 py-1.5 text-xs font-semibold"
            :class="maintenanceOn ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700'"
            :disabled="saving === 'maintenance_mode'"
            @click="toggleMaintenance"
          >
            {{ maintenanceOn ? $t('admin.settings.maintenanceOnClickDisable') : $t('admin.settings.maintenanceOffClickEnable') }}
          </button>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="mb-1 text-sm font-medium text-gray-900">{{ $t('admin.settings.businessHours') }}</p>
        <p class="mb-2 text-xs text-gray-500">
          {{ $t('admin.settings.businessHoursHint', { days: $t('admin.settings.dayAbbrevList') }) }}
        </p>
        <textarea v-model="businessHoursDraft" rows="8" class="w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-xs" />
        <BaseButton class="mt-2" :disabled="saving === 'business_hours'" @click="saveBusinessHours">
          {{ saving === 'business_hours' ? $t('admin.settings.saving') : $t('common.save') }}
        </BaseButton>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="mb-1 text-sm font-medium text-gray-900">{{ $t('admin.settings.databaseBackup') }}</p>
        <p class="mb-3 text-xs text-gray-500">
          {{ $t('admin.settings.backupHint') }} <code>pg_dump</code> {{ $t('admin.settings.backupHintOf') }} <code>smartresto</code> {{ $t('admin.settings.backupHintTail') }}
        </p>
        <p v-if="backups?.lastBackupAt" class="mb-3 text-xs text-gray-600">
          {{ $t('admin.settings.lastBackup') }} {{ new Date(backups.lastBackupAt).toLocaleString() }}
        </p>
        <div class="mb-3 grid grid-cols-2 gap-2">
          <label class="text-xs text-gray-600">
            {{ $t('admin.settings.schedule') }}
            <input v-model="scheduleDraft" class="mt-1 w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
          </label>
          <label class="text-xs text-gray-600">
            {{ $t('admin.settings.retentionDays') }}
            <input v-model.number="retentionDraft" type="number" min="7" max="365" class="mt-1 w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
          </label>
        </div>
        <div class="flex gap-2">
          <BaseButton variant="secondary" :disabled="saving === 'backup_settings'" @click="saveBackupSettings">
            {{ saving === 'backup_settings' ? $t('admin.settings.saving') : $t('admin.settings.saveSchedule') }}
          </BaseButton>
          <BaseButton :disabled="saving === 'backup'" @click="triggerBackup">
            {{ saving === 'backup' ? $t('admin.settings.backingUp') : $t('admin.settings.backupNow') }}
          </BaseButton>
        </div>
        <ul v-if="backups?.files.length" class="mt-4 divide-y divide-gray-100 text-sm">
          <li v-for="file in backups.files" :key="file.filename" class="flex items-center justify-between py-2">
            <span>
              <span class="font-mono text-xs text-gray-800">{{ file.filename }}</span>
              <span class="ml-2 text-xs text-gray-400">{{ formatBytes(file.sizeBytes) }}</span>
            </span>
            <button class="text-xs font-semibold text-brand hover:underline" @click="downloadBackup(file.filename)">{{ $t('admin.settings.download') }}</button>
          </li>
        </ul>
        <p v-else class="mt-3 text-xs text-gray-400">{{ $t('admin.settings.noBackups') }}</p>
      </div>
    </div>
  </div>
</template>
