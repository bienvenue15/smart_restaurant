<script setup lang="ts">
import type { StaffMember } from '~/types/staffUser';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['manage_staff'] });

const { t } = useI18n();
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
    error.value = e instanceof Error ? e.message : t('staff.team.loadFailed');
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
    error.value = e instanceof Error ? e.message : t('staff.team.addMemberFailed');
  } finally {
    creating.value = false;
  }
}

async function toggleActive(member: StaffMember) {
  try {
    await api.patch(`/staff/users/${member.id}`, { isActive: !member.isActive });
    await loadTeam();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.team.updateMemberFailed');
  }
}

function roleLabel(role: string): string {
  return t(`staff.team.role.${role.toLowerCase()}`);
}

const liveRefresh = useLiveRefresh('/staff/events', loadTeam, ['team_updated']);
onMounted(() => {
  loadTeam();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.team')" :subtitle="$t('staff.pages.teamSub')" />

    <form class="staff-card mb-5 flex flex-wrap items-end gap-3" @submit.prevent="addMember">
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.team.fullNameLabel') }}</label>
        <input v-model="form.fullName" class="staff-input" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.team.usernameLabel') }}</label>
        <input v-model="form.username" class="staff-input" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.team.passwordLabel') }}</label>
        <input v-model="form.password" type="password" class="staff-input" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.team.roleLabel') }}</label>
        <select v-model="form.role" class="staff-select">
          <option v-for="role in roles" :key="role" :value="role">{{ roleLabel(role) }}</option>
        </select>
      </div>
      <BaseButton type="submit" :disabled="creating">{{ $t('staff.team.addMember') }}</BaseButton>
    </form>

    <p v-if="error" class="staff-error">{{ error }}</p>
    <div v-if="loading" class="staff-empty">{{ $t('staff.team.loading') }}</div>
    <div v-else-if="team.length === 0" class="staff-empty">{{ $t('staff.team.noMembers') }}</div>

    <div v-else class="staff-card overflow-x-auto !p-0">
      <table class="w-full text-sm">
        <thead class="bg-surface text-left text-xs uppercase tracking-wide text-ink-muted">
          <tr>
            <th class="px-4 py-3">{{ $t('common.name') }}</th>
            <th class="px-4 py-3">{{ $t('staff.team.usernameLabel') }}</th>
            <th class="px-4 py-3">{{ $t('staff.team.roleLabel') }}</th>
            <th class="px-4 py-3">{{ $t('common.status') }}</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="member in team" :key="member.id" class="border-t border-gray-100">
            <td class="px-4 py-3 font-display font-semibold text-ink">{{ member.fullName }}</td>
            <td class="px-4 py-3 text-ink-muted">{{ member.username }}</td>
            <td class="px-4 py-3">
              <span class="staff-chip bg-surface text-ink-muted">{{ roleLabel(member.role) }}</span>
            </td>
            <td class="px-4 py-3">
              <span
                class="staff-chip"
                :class="member.isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500'"
              >
                {{ member.isActive ? $t('common.active') : $t('common.inactive') }}
              </span>
            </td>
            <td class="px-4 py-3">
              <BaseButton variant="secondary" @click="toggleActive(member)">
                {{ member.isActive ? $t('staff.team.deactivate') : $t('staff.team.activate') }}
              </BaseButton>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
