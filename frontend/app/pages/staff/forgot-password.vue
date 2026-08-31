<script setup lang="ts">
definePageMeta({ layout: 'marketing' });

useSeoMeta({
  title: 'Forgot password — Smart Restaurant',
  description: 'Request a password reset link for your staff account.',
});

const identifier = ref('');
const loading = ref(false);
const sent = ref(false);
const error = ref<string | null>(null);
const { t } = useI18n();
const api = useApi();

async function submit() {
  error.value = null;
  loading.value = true;
  try {
    await api.post('/auth/forgot-password', { identifier: identifier.value });
    sent.value = true;
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('auth.forgotFailed');
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
        <h1 class="mt-4 font-display text-2xl font-bold">{{ $t('auth.forgotTitle') }}</h1>
        <p class="mt-1 text-sm text-[#bcd6ca]">{{ $t('auth.forgotSubtitle') }}</p>
      </div>

      <div class="rounded-2xl border border-line bg-card p-6 shadow-brand sm:p-8">
        <p v-if="sent" class="text-sm text-emerald-800">{{ $t('auth.forgotSent') }}</p>
        <form v-else class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="block text-sm font-semibold text-ink" for="identifier">{{ $t('auth.identifier') }}</label>
            <input
              id="identifier"
              v-model="identifier"
              type="text"
              required
              autocomplete="username"
              class="staff-input mt-1"
            />
          </div>
          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
          <button
            type="submit"
            :disabled="loading"
            class="w-full rounded-full bg-brand py-3 text-sm font-bold text-[#3a2a05] transition hover:bg-brand-dark hover:text-white disabled:opacity-60"
          >
            {{ loading ? $t('auth.forgotSending') : $t('auth.forgotSubmit') }}
          </button>
        </form>
        <p class="mt-5 text-center text-sm text-ink-muted">
          <NuxtLink to="/staff/login" class="font-semibold text-brand hover:underline">{{ $t('auth.signIn') }}</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>
