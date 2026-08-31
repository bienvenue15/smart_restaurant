<script setup lang="ts">
definePageMeta({ layout: 'marketing' });

useSeoMeta({
  title: 'Reset password — Smart Restaurant',
  description: 'Choose a new password for your staff account.',
});

const route = useRoute();
const token = computed(() => String(route.query.token ?? ''));
const password = ref('');
const confirmPassword = ref('');
const loading = ref(false);
const done = ref(false);
const error = ref<string | null>(null);
const { t } = useI18n();
const api = useApi();

async function submit() {
  error.value = null;
  if (password.value !== confirmPassword.value) {
    error.value = t('auth.mismatch');
    return;
  }
  if (!token.value) {
    error.value = t('auth.invalidToken');
    return;
  }
  loading.value = true;
  try {
    await api.post('/auth/reset-password', { token: token.value, password: password.value });
    done.value = true;
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('auth.invalidToken');
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="bg-forest bg-diamonds px-4 py-16 sm:px-6">
    <div class="mx-auto w-full max-w-md">
      <div class="mb-8 text-center text-white">
        <div class="flex justify-center">
          <BrandMark :size="56" tone="onDark" />
        </div>
        <h1 class="mt-4 font-display text-2xl font-bold">{{ $t('auth.resetTitle') }}</h1>
      </div>

      <div class="rounded-2xl border border-line bg-card p-6 shadow-brand sm:p-8">
        <p v-if="!token && !done" class="text-sm text-red-600">{{ $t('auth.invalidToken') }}</p>
        <p v-else-if="done" class="text-sm text-emerald-800">{{ $t('auth.resetSuccess') }}</p>
        <form v-else class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="block text-sm font-semibold text-ink" for="new-password">{{ $t('auth.newPassword') }}</label>
            <input
              id="new-password"
              v-model="password"
              type="password"
              required
              minlength="6"
              autocomplete="new-password"
              class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-ink" for="confirm-password">{{ $t('auth.confirmPassword') }}</label>
            <input
              id="confirm-password"
              v-model="confirmPassword"
              type="password"
              required
              minlength="6"
              autocomplete="new-password"
              class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
            />
          </div>
          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
          <button
            type="submit"
            :disabled="loading"
            class="w-full rounded-full bg-brand py-3 text-sm font-bold text-[#3a2a05] transition hover:bg-brand-dark hover:text-white disabled:opacity-60"
          >
            {{ loading ? $t('auth.resetting') : $t('auth.resetSubmit') }}
          </button>
        </form>
        <p class="mt-5 text-center text-sm text-ink-muted">
          <NuxtLink to="/staff/login" class="font-semibold text-brand hover:underline">{{ $t('auth.signIn') }}</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>
