<script setup lang="ts">
import type { AdminRestaurant } from '~/types/admin';

definePageMeta({ middleware: 'admin-auth', layout: 'admin' });

const api = useApi();
const restaurants = ref<AdminRestaurant[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creating = ref(false);
const lastCreatedPassword = ref<string | null>(null);

const form = ref({ restaurantName: '', ownerName: '', email: '', subscriptionPlan: 'TRIAL' });
const plans = ['TRIAL', 'BASIC', 'PREMIUM', 'ENTERPRISE'];

async function loadRestaurants() {
  loading.value = true;
  error.value = null;
  try {
    restaurants.value = await api.get<AdminRestaurant[]>('/admin/restaurants');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load restaurants';
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
    error.value = e instanceof Error ? e.message : 'Failed to create restaurant';
  } finally {
    creating.value = false;
  }
}

async function toggleStatus(restaurant: AdminRestaurant) {
  try {
    await api.patch(`/admin/restaurants/${restaurant.id}/status`, { isActive: !restaurant.isActive });
    await loadRestaurants();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to update restaurant';
  }
}

async function extendSubscription(restaurant: AdminRestaurant) {
  try {
    await api.post(`/admin/restaurants/${restaurant.id}/extend-subscription`, { additionalDays: 30 });
    await loadRestaurants();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to extend subscription';
  }
}

onMounted(loadRestaurants);
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Restaurants</h1>

    <form class="mb-4 flex flex-wrap items-end gap-2" @submit.prevent="createRestaurant">
      <div>
        <label class="block text-xs text-gray-500">Restaurant name</label>
        <input v-model="form.restaurantName" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">Owner name</label>
        <input v-model="form.ownerName" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">Email</label>
        <input v-model="form.email" type="email" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500">Plan</label>
        <select v-model="form.subscriptionPlan" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
          <option v-for="p in plans" :key="p" :value="p">{{ p }}</option>
        </select>
      </div>
      <BaseButton type="submit" :disabled="creating">Onboard restaurant</BaseButton>
    </form>

    <p v-if="lastCreatedPassword" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
      Temporary password (shown once — relay it securely to the owner): <strong>{{ lastCreatedPassword }}</strong>
    </p>
    <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
    <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

    <table v-else class="w-full overflow-hidden rounded-lg border border-gray-200 bg-white text-sm">
      <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
        <tr>
          <th class="px-4 py-2">Name</th>
          <th class="px-4 py-2">Email</th>
          <th class="px-4 py-2">Plan</th>
          <th class="px-4 py-2">Subscription ends</th>
          <th class="px-4 py-2">Status</th>
          <th class="px-4 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in restaurants" :key="r.id" class="border-t border-gray-100">
          <td class="px-4 py-2">{{ r.name }}</td>
          <td class="px-4 py-2 text-gray-500">{{ r.email }}</td>
          <td class="px-4 py-2">{{ r.subscriptionPlan }}</td>
          <td class="px-4 py-2">{{ r.subscriptionEnd ? new Date(r.subscriptionEnd).toLocaleDateString() : '—' }}</td>
          <td class="px-4 py-2">{{ r.isActive ? 'Active' : 'Inactive' }}</td>
          <td class="flex gap-2 px-4 py-2">
            <BaseButton variant="secondary" @click="extendSubscription(r)">+30 days</BaseButton>
            <BaseButton :variant="r.isActive ? 'danger' : 'primary'" @click="toggleStatus(r)">
              {{ r.isActive ? 'Deactivate' : 'Activate' }}
            </BaseButton>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
