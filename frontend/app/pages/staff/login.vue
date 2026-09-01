<script setup lang="ts">
definePageMeta({ layout: 'marketing' });

useSeoMeta({
  title: 'Staff Login — Smart Restaurant',
  description: 'Sign in to the Smart Restaurant staff portal.',
});

const username = ref('');
const password = ref('');
const totpCode = ref('');
const pendingToken = ref<string | null>(null);
const error = ref<string | null>(null);
const loading = ref(false);
const { t } = useI18n();

const api = useApi();
const auth = useAuthStore();
const router = useRouter();
const toast = useToast();
const adminPath = useAdminPath();

type StaffSession = import('~/composables/useAuthStore').StaffSession;
type LoginResult = {
  requiresTwoFactor?: boolean;
  pendingToken?: string;
  accessToken?: string;
  staff: StaffSession;
};

function goAfterLogin(staff: StaffSession) {
  if (staff.role === 'SUPER_ADMIN') return router.push(adminPath.path('dashboard'));
  return router.push(staffHomePath(staff));
}

async function handleSubmit() {
  error.value = null;
  loading.value = true;
  try {
    if (pendingToken.value) {
      const result = await api.post<LoginResult>(
        '/auth/login/2fa',
        {
          pendingToken: pendingToken.value,
          code: totpCode.value,
        },
        { successMessage: false },
      );
      if (!result.accessToken) throw new Error(t('auth.failed'));
      auth.setSession(result.accessToken, result.staff);
      toast.success(t('auth.signedIn'));
      await goAfterLogin(result.staff);
      return;
    }

    const result = await api.post<LoginResult>(
      '/auth/login',
      { username: username.value.trim(), password: password.value },
      { successMessage: false },
    );
    if (result.requiresTwoFactor && result.pendingToken) {
      pendingToken.value = result.pendingToken;
      return;
    }
    if (!result.accessToken) throw new Error(t('auth.failed'));
    auth.setSession(result.accessToken, result.staff);
    toast.success(t('auth.signedIn'));
    await goAfterLogin(result.staff);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('auth.failed');
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
        <h1 class="mt-4 font-display text-2xl font-bold">{{ pendingToken ? $t('auth.twoFactor.title') : $t('auth.title') }}</h1>
        <p class="mt-1 text-sm text-[#bcd6ca]">{{ pendingToken ? $t('auth.twoFactor.enterCode') : $t('auth.subtitle') }}</p>
      </div>

      <form class="rounded-2xl border border-line bg-card p-6 shadow-brand sm:p-8" @submit.prevent="handleSubmit">
        <template v-if="!pendingToken">
          <div>
            <label class="block text-sm font-semibold text-ink" for="username">{{ $t('auth.username') }}</label>
            <input
              id="username"
              v-model="username"
              type="text"
              required
              autocomplete="username"
              class="staff-input mt-1"
            />
          </div>

          <div class="mt-4">
            <label class="block text-sm font-semibold text-ink" for="password">{{ $t('auth.password') }}</label>
            <input
              id="password"
              v-model="password"
              type="password"
              required
              autocomplete="current-password"
              class="staff-input mt-1"
            />
          </div>
        </template>

        <div v-else>
          <label class="block text-sm font-semibold text-ink" for="totp">{{ $t('auth.twoFactor.code') }}</label>
          <input
            id="totp"
            v-model="totpCode"
            inputmode="numeric"
            pattern="\d{6}"
            maxlength="6"
            required
            autocomplete="one-time-code"
            class="staff-input mt-1"
          />
        </div>

        <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="mt-6 w-full rounded-full bg-brand py-3 text-sm font-bold text-[#3a2a05] transition hover:bg-brand-dark hover:text-white disabled:opacity-60"
        >
          {{ loading ? $t('auth.signingIn') : pendingToken ? $t('auth.twoFactor.verify') : $t('auth.signIn') }}
        </button>

        <p class="mt-5 text-center text-sm text-ink-muted">
          <NuxtLink to="/staff/forgot-password" class="font-semibold text-brand hover:underline">{{ $t('auth.forgotPassword') }}</NuxtLink>
        </p>
        <p class="mt-3 text-center text-sm text-ink-muted">
          <NuxtLink to="/" class="font-semibold text-brand hover:underline">{{ $t('auth.backHome') }}</NuxtLink>
          ·
          <NuxtLink to="/register" class="font-semibold text-brand hover:underline">{{ $t('auth.startTrial') }}</NuxtLink>
        </p>
      </form>
    </div>
  </div>
</template>
