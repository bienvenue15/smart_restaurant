<script setup lang="ts">
import type { StaffMember } from '~/types/staffUser';

definePageMeta({ middleware: 'staff-auth', layout: 'staff' });

const api = useApi();
const team = ref<StaffMember[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creating = ref(false);

const form = ref({ username: '', password: '', fullName: '', role: 'WAITER' as StaffMember['role'] });
const roles: StaffMember['role'][] = ['ADMIN', 'MANAGER', 'WAITER', 'KITCHEN', 'CASHIER'];

async function loadTeam() {
  loading.value = true;
  error.value = null;
  try {
    team.value = await api.get<StaffMember[]>('/staff/users');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load team';
  } finally {
    loading.value = false;
  }
}

async function addMember() {
  if (!form.value.username || !form.value.password || !form.value.fullName) return;
  creating.value = true;
  try {
    await api.post('/staff/users', form.value);
    form.value = { username: '', password: '', fullName: '', role: 'WAITER' };
    await loadTeam();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to add team member';
  } finally {
    creating.value = false;
  }
}

async function toggleActive(member: StaffMember) {
  try {
    await api.patch(`/staff/users/${member.id}`, { isActive: !member.isActive });
    await loadTeam();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to update team member';
  }
}

onMounted(loadTeam);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Team</h1>

    <form class="mb-6 flex flex-wrap items-end gap-2" @submit.prevent="addMember">
      <div>
        <label class="block text-xs text-gray-500">Full name</label>
        <input v-model="form.fullName" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">Username</label>
        <input v-model="form.username" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">Password</label>
        <input v-model="form.password" type="password" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">Role</label>
        <select v-model="form.role" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
          <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
        </select>
      </div>
      <BaseButton type="submit" :disabled="creating">Add member</BaseButton>
    </form>

    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

    <table v-else class="w-full overflow-hidden rounded-lg border border-gray-200 bg-white text-sm">
      <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
        <tr>
          <th class="px-4 py-2">Name</th>
          <th class="px-4 py-2">Username</th>
          <th class="px-4 py-2">Role</th>
          <th class="px-4 py-2">Status</th>
          <th class="px-4 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="member in team" :key="member.id" class="border-t border-gray-100">
          <td class="px-4 py-2">{{ member.fullName }}</td>
          <td class="px-4 py-2 text-gray-500">{{ member.username }}</td>
          <td class="px-4 py-2">{{ member.role }}</td>
          <td class="px-4 py-2">{{ member.isActive ? 'Active' : 'Inactive' }}</td>
          <td class="px-4 py-2">
            <BaseButton variant="secondary" @click="toggleActive(member)">
              {{ member.isActive ? 'Deactivate' : 'Activate' }}
            </BaseButton>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
