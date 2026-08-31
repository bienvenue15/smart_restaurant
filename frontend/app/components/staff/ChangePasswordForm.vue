<script setup lang="ts">
const api = useApi();
const { t } = useI18n();

const currentPassword = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const saving = ref(false);
const error = ref<string | null>(null);
const saved = ref(false);

async function submit() {
  error.value = null;
  saved.value = false;
  if (newPassword.value !== confirmPassword.value) {
    error.value = t('auth.mismatch');
    return;
  }
  saving.value = true;
  try {
    await api.post('/auth/change-password', {
      currentPassword: currentPassword.value,
      newPassword: newPassword.value,
    });
    currentPassword.value = '';
    newPassword.value = '';
    confirmPassword.value = '';
    saved.value = true;
    setTimeout(() => (saved.value = false), 3000);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('auth.twoFactor.disableFailed');
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="staff-card">
    <p class="mb-3 font-display font-bold text-ink">{{ $t('staff.pages.changePassword') }}</p>
    <form class="space-y-3" @submit.prevent="submit">
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted" for="current-password">{{ $t('staff.pages.currentPassword') }}</label>
        <input id="current-password" v-model="currentPassword" type="password" required minlength="6" autocomplete="current-password" class="staff-input" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted" for="new-password">{{ $t('auth.newPassword') }}</label>
        <input id="new-password" v-model="newPassword" type="password" required minlength="6" autocomplete="new-password" class="staff-input" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted" for="confirm-password">{{ $t('auth.confirmPassword') }}</label>
        <input id="confirm-password" v-model="confirmPassword" type="password" required minlength="6" autocomplete="new-password" class="staff-input" />
      </div>
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <div class="flex items-center gap-2">
        <BaseButton type="submit" :disabled="saving">{{ saving ? $t('auth.resetting') : $t('auth.resetSubmit') }}</BaseButton>
        <span v-if="saved" class="text-xs font-semibold text-emerald-700">{{ $t('staff.pages.passwordUpdated') }}</span>
      </div>
    </form>
  </div>
</template>
