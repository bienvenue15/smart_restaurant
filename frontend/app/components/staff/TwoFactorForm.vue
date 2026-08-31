<script setup lang="ts">
const api = useApi();
const { t } = useI18n();

const enabled = ref(false);
const wantEnabled = ref(false);
const loading = ref(true);
const setup = ref<{ secret: string; qrDataUrl: string } | null>(null);
const code = ref('');
const password = ref('');
const error = ref<string | null>(null);
const busy = ref(false);

const showSetup = computed(() => wantEnabled.value && !enabled.value && Boolean(setup.value));
const showDisable = computed(() => enabled.value && !wantEnabled.value);

async function load() {
  loading.value = true;
  try {
    const status = await api.get<{ enabled: boolean }>('/auth/2fa');
    enabled.value = status.enabled;
    wantEnabled.value = status.enabled;
  } catch {
    enabled.value = false;
    wantEnabled.value = false;
  } finally {
    loading.value = false;
  }
}

async function beginSetup() {
  error.value = null;
  busy.value = true;
  try {
    setup.value = await api.post<{ secret: string; qrDataUrl: string }>('/auth/2fa/setup');
    code.value = '';
  } catch (e) {
    wantEnabled.value = false;
    setup.value = null;
    error.value = e instanceof Error ? e.message : t('auth.twoFactor.setupFailed');
  } finally {
    busy.value = false;
  }
}

async function cancelSetup() {
  error.value = null;
  busy.value = true;
  try {
    await api.post('/auth/2fa/cancel');
  } catch {
    // Local cancel still stands — leftover pending secret is overwritten on the next setup.
  } finally {
    setup.value = null;
    code.value = '';
    busy.value = false;
  }
}

async function onToggle(event: Event) {
  const next = (event.target as HTMLInputElement).checked;
  error.value = null;
  if (next) {
    wantEnabled.value = true;
    if (!enabled.value && !setup.value) await beginSetup();
    return;
  }
  if (setup.value) await cancelSetup();
  wantEnabled.value = false;
}

async function confirmEnable() {
  error.value = null;
  busy.value = true;
  try {
    await api.post('/auth/2fa/enable', { code: code.value });
    enabled.value = true;
    wantEnabled.value = true;
    setup.value = null;
    code.value = '';
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('auth.twoFactor.invalidCode');
  } finally {
    busy.value = false;
  }
}

async function disable() {
  error.value = null;
  busy.value = true;
  try {
    await api.post('/auth/2fa/disable', { password: password.value, code: code.value });
    enabled.value = false;
    wantEnabled.value = false;
    password.value = '';
    code.value = '';
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('auth.twoFactor.disableFailed');
  } finally {
    busy.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <label class="flex cursor-pointer items-start gap-3">
      <input
        type="checkbox"
        class="mt-1 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand"
        :checked="wantEnabled"
        :disabled="busy || loading"
        @change="onToggle"
      />
      <span>
        <span class="block text-sm font-medium text-gray-900">{{ $t('auth.twoFactor.title') }}</span>
        <span class="mt-0.5 block text-xs text-gray-500">{{ $t('auth.twoFactor.subtitle') }}</span>
      </span>
    </label>

    <p v-if="loading" class="mt-3 text-sm text-gray-500">{{ $t('common.loading') }}</p>

    <form v-else-if="showSetup" class="mt-4 space-y-3 border-t border-gray-100 pt-4" @submit.prevent="confirmEnable">
      <p class="text-xs text-gray-500">{{ $t('auth.twoFactor.scanHint') }}</p>
      <img :src="setup!.qrDataUrl" alt="" class="h-44 w-44 rounded-xl border border-gray-200 bg-white p-2" />
      <p class="break-all font-mono text-xs text-gray-500">{{ setup!.secret }}</p>
      <div>
        <label class="mb-1 block text-xs font-medium text-gray-500" for="totp-enable">{{ $t('auth.twoFactor.code') }}</label>
        <input
          id="totp-enable"
          v-model="code"
          inputmode="numeric"
          pattern="\d{6}"
          maxlength="6"
          required
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
          autocomplete="one-time-code"
        />
      </div>
      <BaseButton type="submit" :disabled="busy">{{ $t('auth.twoFactor.enable') }}</BaseButton>
    </form>

    <form v-else-if="showDisable" class="mt-4 space-y-3 border-t border-gray-100 pt-4" @submit.prevent="disable">
      <p class="text-xs text-gray-500">{{ $t('auth.twoFactor.confirmDisable') }}</p>
      <div>
        <label class="mb-1 block text-xs font-medium text-gray-500" for="totp-password">{{ $t('auth.password') }}</label>
        <input
          id="totp-password"
          v-model="password"
          type="password"
          required
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
          autocomplete="current-password"
        />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-gray-500" for="totp-disable">{{ $t('auth.twoFactor.code') }}</label>
        <input
          id="totp-disable"
          v-model="code"
          inputmode="numeric"
          pattern="\d{6}"
          maxlength="6"
          required
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
          autocomplete="one-time-code"
        />
      </div>
      <BaseButton type="submit" variant="danger" :disabled="busy">{{ $t('auth.twoFactor.disable') }}</BaseButton>
    </form>

    <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
