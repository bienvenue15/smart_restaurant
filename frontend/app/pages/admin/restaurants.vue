<script setup lang="ts">
import type { AdminRestaurant } from '~/types/admin';

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const { t } = useI18n();
const api = useApi();
const restaurants = ref<AdminRestaurant[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creating = ref(false);
const lastCreatedPassword = ref<string | null>(null);
const deleting = ref<AdminRestaurant | null>(null);
const showPurge = ref(false);
const confirmSlug = ref('');
const purgeBusy = ref(false);

const form = ref({ restaurantName: '', ownerName: '', email: '', subscriptionPlan: 'TRIAL' });
const plans = ['TRIAL', 'BASIC', 'PREMIUM', 'ENTERPRISE'];

const route = useRoute();
const search = ref('');
const planFilter = ref('');
const statusFilter = ref(typeof route.query.status === 'string' ? route.query.status : '');
const filteredRestaurants = computed(() => {
  const needle = search.value.trim().toLowerCase();
  return restaurants.value.filter((r) => {
    if (needle && !r.name.toLowerCase().includes(needle) && !r.email.toLowerCase().includes(needle)) return false;
    if (planFilter.value && r.subscriptionPlan !== planFilter.value) return false;
    if (statusFilter.value === 'active' && !r.isActive) return false;
    if (statusFilter.value === 'inactive' && r.isActive) return false;
    return true;
  });
});

async function loadRestaurants() {
  loading.value = true;
  error.value = null;
  try {
    restaurants.value = await api.get<AdminRestaurant[]>('/admin/restaurants');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.restaurants.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function createRestaurant() {
  if (!form.value.restaurantName || !form.value.ownerName || !form.value.email) return;
  creating.value = true;
  lastCreatedPassword.value = null;
  try {
    // temporaryPassword is returned ONCE here — never emailed/logged in
    // plaintext (docs/SECURITY_AUDIT.md #11 fixes the legacy behavior).
    // Relay it to the restaurant owner through your own secure channel.
    const result = await api.post<{ temporaryPassword: string }>('/admin/restaurants', form.value);
    lastCreatedPassword.value = result.temporaryPassword;
    form.value = { restaurantName: '', ownerName: '', email: '', subscriptionPlan: 'TRIAL' };
    await loadRestaurants();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.restaurants.createFailed');
  } finally {
    creating.value = false;
  }
}

async function toggleStatus(restaurant: AdminRestaurant) {
  try {
    await api.patch(`/admin/restaurants/${restaurant.id}/status`, { isActive: !restaurant.isActive });
    await loadRestaurants();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.restaurants.updateFailed');
  }
}

async function changePlan(restaurant: AdminRestaurant, event: Event) {
  const plan = (event.target as HTMLSelectElement).value;
  if (!plan || plan === restaurant.subscriptionPlan) return;
  try {
    await api.patch(`/admin/restaurants/${restaurant.id}/plan`, { subscriptionPlan: plan });
    await loadRestaurants();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.restaurants.updateFailed');
    await loadRestaurants();
  }
}

async function extendSubscription(restaurant: AdminRestaurant) {
  try {
    await api.post(`/admin/restaurants/${restaurant.id}/extend-subscription`, { additionalDays: 30 });
    await loadRestaurants();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.restaurants.extendFailed');
  }
}

function openPurge(restaurant: AdminRestaurant) {
  deleting.value = restaurant;
  confirmSlug.value = '';
  showPurge.value = true;
}

async function hardDelete() {
  if (!deleting.value) return;
  purgeBusy.value = true;
  try {
    await api.post(`/admin/restaurants/${deleting.value.id}/hard-delete`, { confirmSlug: confirmSlug.value });
    showPurge.value = false;
    deleting.value = null;
    await loadRestaurants();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('admin.restaurants.deleteFailed');
  } finally {
    purgeBusy.value = false;
  }
}

const liveRefresh = useLiveRefresh('/staff/events', loadRestaurants);
onMounted(() => {
  loadRestaurants();
  liveRefresh.start();
});
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">{{ $t('admin.nav.restaurants') }}</h1>

    <form class="mb-4 flex flex-wrap items-end gap-2" @submit.prevent="createRestaurant">
      <div>
        <label class="block text-xs text-gray-500">{{ $t('admin.restaurants.restaurantName') }}</label>
        <input v-model="form.restaurantName" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">{{ $t('admin.restaurants.ownerName') }}</label>
        <input v-model="form.ownerName" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">{{ $t('admin.restaurants.email') }}</label>
        <input v-model="form.email" type="email" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">{{ $t('admin.restaurants.plan') }}</label>
        <select v-model="form.subscriptionPlan" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
          <option v-for="p in plans" :key="p" :value="p">{{ p }}</option>
        </select>
      </div>
      <BaseButton type="submit" :disabled="creating">{{ $t('admin.restaurants.onboard') }}</BaseButton>
    </form>

    <p v-if="lastCreatedPassword" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
      {{ $t('admin.restaurants.tempPasswordNotice') }} <strong>{{ lastCreatedPassword }}</strong>
    </p>
    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

    <div v-if="!loading" class="mb-3 flex flex-wrap items-center gap-2">
      <input v-model="search" type="text" :placeholder="$t('admin.restaurants.searchPlaceholder')" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      <select v-model="planFilter" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
        <option value="">{{ $t('admin.restaurants.allPlans') }}</option>
        <option v-for="p in plans" :key="p" :value="p">{{ p }}</option>
      </select>
      <select v-model="statusFilter" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
        <option value="">{{ $t('admin.restaurants.allStatuses') }}</option>
        <option value="active">{{ $t('common.active') }}</option>
        <option value="inactive">{{ $t('common.inactive') }}</option>
      </select>
      <span class="text-xs text-gray-400">{{ $t('admin.restaurants.resultCount', { count: filteredRestaurants.length, total: restaurants.length }) }}</span>
    </div>

    <p v-if="loading" class="text-sm text-gray-500">{{ $t('common.loading') }}</p>

    <table v-else class="w-full overflow-hidden rounded-lg border border-gray-200 bg-white text-sm">
      <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
        <tr>
          <th class="px-4 py-2">{{ $t('common.name') }}</th>
          <th class="px-4 py-2">{{ $t('admin.restaurants.email') }}</th>
          <th class="px-4 py-2">{{ $t('admin.restaurants.plan') }}</th>
          <th class="px-4 py-2">{{ $t('admin.restaurants.heardAbout') }}</th>
          <th class="px-4 py-2">{{ $t('admin.restaurants.subscriptionEnds') }}</th>
          <th class="px-4 py-2">{{ $t('common.status') }}</th>
          <th class="px-4 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in filteredRestaurants" :key="r.id" class="border-t border-gray-100">
          <td class="px-4 py-2">{{ r.name }}</td>
          <td class="px-4 py-2 text-gray-500">{{ r.email }}</td>
          <td class="px-4 py-2">
            <select
              class="rounded-md border border-gray-300 px-1.5 py-1 text-xs"
              :value="r.subscriptionPlan"
              @change="changePlan(r, $event)"
            >
              <option v-for="p in plans" :key="p" :value="p">{{ p }}</option>
            </select>
          </td>
          <td class="px-4 py-2 text-gray-500">
            {{ r.heardAboutUs ? $t(`heardAbout.platform.${r.heardAboutUs}`) : $t('heardAbout.unknown') }}
          </td>
          <td class="px-4 py-2">{{ r.subscriptionEnd ? new Date(r.subscriptionEnd).toLocaleDateString() : '—' }}</td>
          <td class="px-4 py-2">{{ r.isActive ? $t('common.active') : $t('common.inactive') }}</td>
          <td class="flex gap-2 px-4 py-2">
            <BaseButton variant="secondary" @click="extendSubscription(r)">{{ $t('admin.restaurants.extend30') }}</BaseButton>
            <BaseButton :variant="r.isActive ? 'danger' : 'primary'" @click="toggleStatus(r)">
              {{ r.isActive ? $t('admin.restaurants.deactivate') : $t('admin.restaurants.activate') }}
            </BaseButton>
            <BaseButton variant="danger" @click="openPurge(r)">{{ $t('common.delete') }}</BaseButton>
          </td>
        </tr>
      </tbody>
    </table>

    <BaseModal v-model="showPurge">
      <p class="font-display font-bold text-ink">{{ $t('admin.restaurants.purgeTitle') }}</p>
      <p class="mt-2 text-sm text-ink-muted">
        {{ $t('admin.restaurants.purgeBodyPrefix') }} <strong>{{ deleting?.slug }}</strong> {{ $t('admin.restaurants.purgeBodySuffix') }}
      </p>
      <input v-model="confirmSlug" class="staff-input mt-3" :placeholder="deleting?.slug" />
      <div class="mt-4 flex justify-end gap-2">
        <BaseButton variant="secondary" @click="showPurge = false">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="danger" :disabled="purgeBusy || confirmSlug !== deleting?.slug" @click="hardDelete">{{ $t('admin.restaurants.deleteForever') }}</BaseButton>
      </div>
    </BaseModal>
  </div>
</template>
