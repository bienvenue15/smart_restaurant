<script setup lang="ts">
const username = ref('');
const password = ref('');
const error = ref<string | null>(null);
const loading = ref(false);

const api = useApi();
const auth = useAuthStore();
const router = useRouter();

async function handleSubmit() {
  error.value = null;
  loading.value = true;
  try {
    const result = await api.post<{ accessToken: string; refreshToken: string; staff: import('~/composables/useAuthStore').StaffSession }>(
      '/auth/login',
      { username: username.value, password: password.value },
    );
    auth.setSession(result.accessToken, result.staff);
    await router.push('/staff/dashboard');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Login failed';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center px-6">
    <form class="w-full max-w-sm space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm" @submit.prevent="handleSubmit">
      <h1 class="text-xl font-semibold text-gray-900">Staff login</h1>

      <div>
        <label class="block text-sm font-medium text-gray-700" for="username">Username</label>
        <input
          id="username"
          v-model="username"
          type="text"
          required
          class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700" for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
          required
          class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
        />
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <BaseButton type="submit" :disabled="loading" class="w-full">
        {{ loading ? 'Signing in…' : 'Sign in' }}
      </BaseButton>
    </form>
  </div>
</template>
