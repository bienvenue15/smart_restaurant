<script setup lang="ts">
import type { PlatformUser } from '~/types/admin';

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const { t } = useI18n();
const api = useApi();
const prompt = useConfirm();
const users = ref<PlatformUser[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const busyId = ref<string | null>(null);
const revealedPassword = ref<{ userId: string; username: string; password: string } | null>(null);

const search = ref('');
const roleFilter = ref('');
const statusFilter = ref('');
const roles = ['ADMIN', 'MANAGER', 'WAITER', 'KITCHEN', 'CASHIER'];
const filteredUsers = computed(() => {
  const needle = search.value.trim().toLowerCase();
  return users.value.filter((u) => {
    if (needle && !u.fullName.toLowerCase().includes(needle) && !(u.restaurant?.name.toLowerCase().includes(needle) ?? false)) return false;
    if (roleFilter.value && u.role !== roleFilter.value) return false;
    if (statusFilter.value === 'active' && !u.isActive) return false;
    if (statusFilter.value === 'inactive' && u.isActive) return false;
    return true;
  });
});

async function loadUsers() {
  loading.value = true;
  error.value = null;
  try {
    users.value = await api.get<PlatformUser[]>('/admin/users');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.users.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function toggleStatus(user: PlatformUser) {
  busyId.value = user.id;
  try {
    await api.patch(`/admin/users/${user.id}/status`, { isActive: !user.isActive });
    await loadUsers();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.users.statusUpdateFailed');
  } finally {
    busyId.value = null;
  }
}

async function resetPassword(user: PlatformUser) {
  const ok = await prompt.confirm({
    title: t('admin.users.resetPassword'),
    message: t('admin.users.resetConfirm', { name: user.fullName }),
    confirmLabel: t('admin.users.resetPassword'),
    danger: true,
  });
  if (!ok) return;
  busyId.value = user.id;
  revealedPassword.value = null;
  try {
    const result = await api.post<{ username: string; temporaryPassword: string }>(`/admin/users/${user.id}/reset-password`);
    revealedPassword.value = { userId: user.id, username: result.username, password: result.temporaryPassword };
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.users.resetPasswordFailed');
  } finally {
    busyId.value = null;
  }
}

const liveRefresh = useLiveRefresh('/staff/events', loadUsers);
onMounted(() => {
  loadUsers();
  liveRefresh.start();
});
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">{{ $t('admin.nav.users') }}</h1>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

    <div v-if="revealedPassword" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
      {{ $t('admin.users.newPasswordFor') }} <span class="font-medium">{{ revealedPassword.username }}</span>: <code class="rounded bg-white px-1.5 py-0.5">{{ revealedPassword.password }}</code>
      — {{ $t('admin.users.shownOnceHint') }}
      <button class="ml-2 text-xs underline" @click="revealedPassword = null">{{ $t('common.dismiss') }}</button>
    </div>

    <div v-if="!loading" class="mb-3 flex flex-wrap items-center gap-2">
      <input v-model="search" type="text" :placeholder="$t('admin.users.searchPlaceholder')" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      <select v-model="roleFilter" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
        <option value="">{{ $t('admin.users.allRoles') }}</option>
        <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
      </select>
      <select v-model="statusFilter" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
        <option value="">{{ $t('admin.restaurants.allStatuses') }}</option>
        <option value="active">{{ $t('common.active') }}</option>
        <option value="inactive">{{ $t('common.inactive') }}</option>
      </select>
      <span class="text-xs text-gray-400">{{ $t('admin.restaurants.resultCount', { count: filteredUsers.length, total: users.length }) }}</span>
    </div>

    <p v-if="loading" class="text-sm text-gray-500">{{ $t('common.loading') }}</p>

    <table v-else class="w-full overflow-hidden rounded-lg border border-gray-200 bg-white text-sm">
      <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
        <tr>
          <th class="px-4 py-2">{{ $t('common.name') }}</th>
          <th class="px-4 py-2">{{ $t('admin.users.restaurant') }}</th>
          <th class="px-4 py-2">{{ $t('admin.users.role') }}</th>
          <th class="px-4 py-2">{{ $t('common.status') }}</th>
          <th class="px-4 py-2">{{ $t('common.actions') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="u in filteredUsers" :key="u.id" class="border-t border-gray-100">
          <td class="px-4 py-2">{{ u.fullName }}</td>
          <td class="px-4 py-2 text-gray-500">{{ u.restaurant?.name ?? '—' }}</td>
          <td class="px-4 py-2">{{ u.role }}</td>
          <td class="px-4 py-2">
            <span class="rounded px-2 py-0.5 text-xs font-medium" :class="u.isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
              {{ u.isActive ? $t('common.active') : $t('common.inactive') }}
            </span>
          </td>
          <td class="px-4 py-2">
            <div class="flex gap-2">
              <button
                class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                :disabled="busyId === u.id"
                @click="toggleStatus(u)"
              >
                {{ u.isActive ? $t('admin.users.deactivate') : $t('admin.users.activate') }}
              </button>
              <button
                class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                :disabled="busyId === u.id"
                @click="resetPassword(u)"
              >
                {{ $t('admin.users.resetPassword') }}
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
