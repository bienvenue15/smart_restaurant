<script setup lang="ts">
import type { PlatformUser } from '~/types/admin';

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const api = useApi();
const users = ref<PlatformUser[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

async function loadUsers() {
  loading.value = true;
  error.value = null;
  try {
    users.value = await api.get<PlatformUser[]>('/admin/users');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load platform users';
  } finally {
    loading.value = false;
  }
}

onMounted(loadUsers);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Platform users</h1>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

    <table v-else class="w-full overflow-hidden rounded-lg border border-gray-200 bg-white text-sm">
      <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
        <tr>
          <th class="px-4 py-2">Name</th>
          <th class="px-4 py-2">Restaurant</th>
          <th class="px-4 py-2">Role</th>
          <th class="px-4 py-2">Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="u in users" :key="u.id" class="border-t border-gray-100">
          <td class="px-4 py-2">{{ u.fullName }}</td>
          <td class="px-4 py-2 text-gray-500">{{ u.restaurant?.name ?? '—' }}</td>
          <td class="px-4 py-2">{{ u.role }}</td>
          <td class="px-4 py-2">{{ u.isActive ? 'Active' : 'Inactive' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
