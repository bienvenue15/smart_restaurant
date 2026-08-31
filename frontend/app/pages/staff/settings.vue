<script setup lang="ts">
import type { RestaurantProfile } from '~/types/restaurant';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['manage_settings'] });

const { t } = useI18n();
const api = useApi();
const auth = useAuthStore();
const restaurant = ref<RestaurantProfile | null>(null);
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const saved = ref(false);

const form = ref({
  name: '',
  phone: '',
  address: '',
  city: '',
  logoUrl: '',
  primaryColor: '#2563eb',
  secondaryColor: '#1e40af',
  taxRate: 0,
  serviceCharge: 0,
});

const logoUploading = ref(false);
const runtimeConfig = useRuntimeConfig();

function imageSrc(imageUrl: string | null): string | null {
  return resolveMediaUrl(imageUrl, runtimeConfig.public.apiBaseUrl as string);
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    restaurant.value = await api.get<RestaurantProfile>('/staff/restaurants/me');
    if (restaurant.value) {
      form.value = {
        name: restaurant.value.name,
        phone: restaurant.value.phone ?? '',
        address: restaurant.value.address ?? '',
        city: restaurant.value.city ?? '',
        logoUrl: restaurant.value.logoUrl ?? '',
        primaryColor: restaurant.value.primaryColor,
        secondaryColor: restaurant.value.secondaryColor,
        taxRate: Number(restaurant.value.taxRate),
        serviceCharge: Number(restaurant.value.serviceCharge),
      };
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.settings.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  saved.value = false;
  error.value = null;
  try {
    restaurant.value = await api.patch<RestaurantProfile>('/staff/restaurants/me', form.value);
    if (restaurant.value) {
      auth.setRestaurantBrand({
        name: restaurant.value.name,
        logoUrl: restaurant.value.logoUrl,
        primaryColor: restaurant.value.primaryColor,
        secondaryColor: restaurant.value.secondaryColor,
      });
    }
    saved.value = true;
    setTimeout(() => (saved.value = false), 3000);
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.settings.saveFailed');
  } finally {
    saving.value = false;
  }
}

async function uploadLogo(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  logoUploading.value = true;
  error.value = null;
  try {
    const updated = await api.upload<RestaurantProfile>('/staff/restaurants/me/logo', file);
    restaurant.value = updated;
    form.value.logoUrl = updated.logoUrl ?? '';
    auth.setRestaurantBrand({
      name: updated.name,
      logoUrl: updated.logoUrl,
      primaryColor: updated.primaryColor,
      secondaryColor: updated.secondaryColor,
    });
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.settings.uploadLogoFailed');
  } finally {
    logoUploading.value = false;
    input.value = '';
  }
}

onMounted(load);
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.settings')" :subtitle="$t('staff.pages.settingsSub')" />

    <p v-if="error" class="staff-error">{{ error }}</p>
    <div v-if="loading" class="staff-empty">{{ $t('staff.settings.loading') }}</div>

    <div v-else class="max-w-xl space-y-5">
      <div v-if="restaurant" class="staff-card">
        <p class="mb-2 font-display font-bold text-ink">{{ $t('staff.settings.subscription') }}</p>
        <div class="grid grid-cols-2 gap-2 text-sm text-ink-muted">
          <p>{{ $t('staff.settings.planLabel') }} <span class="font-semibold text-ink">{{ restaurant.plan?.displayName ?? restaurant.subscriptionPlan }}</span></p>
          <p>
            {{ $t('staff.settings.statusLabel') }}
            <span class="font-semibold" :class="restaurant.plan?.subscriptionActive ? 'text-emerald-700' : 'text-red-700'">
              {{ restaurant.plan?.subscriptionActive ? $t('common.active') : $t('staff.settings.expired') }}
            </span>
          </p>
          <p>{{ $t('staff.settings.renewsLabel') }} {{ restaurant.subscriptionEnd ? new Date(restaurant.subscriptionEnd).toLocaleDateString() : '—' }}</p>
          <p>{{ $t('staff.settings.currencyLabel') }} {{ restaurant.currency }}</p>
          <p>{{ $t('staff.settings.maxTablesLabel') }} {{ restaurant.plan?.usage ? `${restaurant.plan.usage.tables} / ${restaurant.plan.limits.maxTables}` : restaurant.plan?.limits.maxTables ?? restaurant.maxTables }}</p>
          <p>{{ $t('staff.settings.maxUsersLabel') }} {{ restaurant.plan?.usage ? `${restaurant.plan.usage.users} / ${restaurant.plan.limits.maxUsers}` : restaurant.plan?.limits.maxUsers ?? restaurant.maxUsers }}</p>
          <p>{{ $t('staff.settings.maxMenuItemsLabel') }} {{ restaurant.plan?.usage ? `${restaurant.plan.usage.menuItems} / ${restaurant.plan.limits.maxMenuItems}` : restaurant.plan?.limits.maxMenuItems ?? '—' }}</p>
          <p>{{ $t('staff.settings.maxOrdersLabel') }} {{ restaurant.plan?.usage ? `${restaurant.plan.usage.ordersThisMonth} / ${restaurant.plan.limits.maxOrdersPerMonth}` : restaurant.plan?.limits.maxOrdersPerMonth ?? '—' }}</p>
        </div>
        <ul v-if="restaurant.plan?.features?.length" class="mt-3 flex flex-wrap gap-1.5">
          <li
            v-for="feature in restaurant.plan.features"
            :key="feature"
            class="rounded-full bg-surface px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-ink-muted"
          >
            {{ $t(`admin.plans.feature.${feature}`, feature.replace(/_/g, ' ')) }}
          </li>
        </ul>
      </div>

      <div class="staff-card">
        <p class="mb-3 font-display font-bold text-ink">{{ $t('staff.settings.restaurantProfile') }}</p>
        <div class="space-y-3">
          <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('common.name') }}</label>
            <input v-model="form.name" type="text" class="staff-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.settings.phoneLabel') }}</label>
            <input v-model="form.phone" type="text" class="staff-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.settings.addressLabel') }}</label>
            <input v-model="form.address" type="text" class="staff-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.settings.cityLabel') }}</label>
            <input v-model="form.city" type="text" class="staff-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.settings.logoLabel') }}</label>
            <div class="flex items-center gap-3">
              <img
                v-if="imageSrc(form.logoUrl)"
                :src="imageSrc(form.logoUrl)!"
                alt=""
                class="h-12 w-12 rounded-xl border border-gray-200 object-contain"
              />
              <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="text-xs" :disabled="logoUploading" @change="uploadLogo" />
            </div>
            <p class="mt-1 text-xs text-ink-muted">{{ logoUploading ? $t('staff.settings.uploading') : $t('staff.settings.logoHint') }}</p>
            <input v-model="form.logoUrl" type="text" class="staff-input mt-2" :placeholder="$t('staff.settings.logoUrlPlaceholder')" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.settings.primaryColorLabel') }}</label>
              <input v-model="form.primaryColor" type="color" class="h-10 w-full rounded-xl border border-gray-200" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.settings.secondaryColorLabel') }}</label>
              <input v-model="form.secondaryColor" type="color" class="h-10 w-full rounded-xl border border-gray-200" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.settings.taxRateLabel') }}</label>
              <input v-model.number="form.taxRate" type="number" min="0" max="100" step="0.01" class="staff-input" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.settings.serviceChargeLabel') }}</label>
              <input v-model.number="form.serviceCharge" type="number" min="0" max="100" step="0.01" class="staff-input" />
            </div>
          </div>
          <div class="flex items-center gap-2">
            <BaseButton :disabled="saving" @click="save">{{ saving ? $t('staff.settings.saving') : $t('common.save') }}</BaseButton>
            <span v-if="saved" class="text-xs font-semibold text-emerald-700">{{ $t('staff.settings.saved') }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
