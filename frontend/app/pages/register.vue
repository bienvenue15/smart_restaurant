<script setup lang="ts">
import { PLATFORM_HEARD_ABOUT, parseRegisterSource, type PlatformHeardAbout } from '~/utils/heardAbout';

definePageMeta({ layout: 'marketing' });

useSeoMeta({
  title: 'Register Your Restaurant — Smart Restaurant',
  description: 'Start a 30-day free trial. Create your restaurant account and staff admin login in minutes.',
});

const api = useApi();
const router = useRouter();

const form = reactive({
  restaurantName: '',
  ownerName: '',
  email: '',
  password: '',
  phone: '',
  tin: '',
  address: '',
  city: 'Kigali',
  heardAboutUs: null as PlatformHeardAbout | null,
});

const showPassword = ref(false);
const loading = ref(false);
const error = ref<string | null>(null);
const success = ref<{ name: string; slug: string } | null>(null);
const { t } = useI18n();
const route = useRoute();

onMounted(() => {
  const fromLink = parseRegisterSource(route.query.src);
  if (fromLink) form.heardAboutUs = fromLink;
});

async function submit() {
  error.value = null;
  loading.value = true;
  try {
    const result = await api.post<{ id: string; slug: string; name: string }>('/restaurants/register', {
      restaurantName: form.restaurantName,
      ownerName: form.ownerName,
      email: form.email,
      password: form.password,
      phone: form.phone || undefined,
      tin: form.tin || undefined,
      address: form.address || undefined,
      city: form.city || 'Kigali',
      heardAboutUs: form.heardAboutUs || undefined,
    });
    success.value = { name: result.name, slug: result.slug };
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('marketing.register.failed');
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="bg-diamonds">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 sm:py-16">
      <div class="mb-10 text-center">
        <span class="mb-3 inline-block rounded-full bg-clay px-4 py-1.5 text-xs font-bold uppercase tracking-[0.1em] text-[#fbeee4]">{{ $t('marketing.nav.trial') }}</span>
        <h1 class="font-display text-3xl font-bold text-forest sm:text-4xl">{{ $t('marketing.register.title') }}</h1>
        <p class="mt-2 text-ink-muted">{{ $t('marketing.register.subtitle') }}</p>
      </div>

      <div v-if="success" class="mx-auto max-w-lg rounded-2xl border border-line bg-card p-8 text-center shadow-brand">
        <p class="font-display text-2xl font-bold text-ink">{{ $t('marketing.register.successTitle') }}</p>
        <p class="mt-3 text-sm text-ink-muted">
          {{ $t('marketing.register.successBody', { name: success.name }) }}
        </p>
        <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
          <NuxtLink
            to="/staff/login"
            class="rounded-full bg-brand px-5 py-3 text-sm font-bold text-[#3a2a05] hover:bg-brand-dark hover:text-white"
          >
            {{ $t('marketing.register.goLogin') }}
          </NuxtLink>
          <button
            type="button"
            class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-semibold text-ink-muted hover:bg-black/5"
            @click="router.push('/')"
          >
            {{ $t('common.back') }}
          </button>
        </div>
      </div>

      <div v-else class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <form class="rounded-2xl border border-line bg-card p-6 shadow-brand sm:p-8" @submit.prevent="submit">
          <h2 class="font-display text-xl font-bold text-forest">{{ $t('marketing.register.account') }}</h2>
          <p class="mt-1 text-sm text-ink-muted">{{ $t('marketing.register.accountHint') }}</p>

          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-semibold" for="restaurantName">{{ $t('marketing.register.restaurantName') }}</label>
              <input
                id="restaurantName"
                v-model="form.restaurantName"
                required
                minlength="2"
                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-semibold" for="ownerName">{{ $t('marketing.register.ownerName') }}</label>
              <input
                id="ownerName"
                v-model="form.ownerName"
                required
                minlength="2"
                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-semibold" for="email">{{ $t('marketing.register.email') }}</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
              />
            </div>
            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-semibold" for="password">{{ $t('marketing.register.password') }}</label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  minlength="6"
                  class="w-full rounded-xl border border-gray-200 px-3 py-2.5 pr-20 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
                />
                <button
                  type="button"
                  class="absolute inset-y-0 right-2 my-auto rounded-lg px-2 text-xs font-semibold text-ink-muted hover:text-ink"
                  @click="showPassword = !showPassword"
                >
                  {{ showPassword ? $t('marketing.register.hide') : $t('marketing.register.show') }}
                </button>
              </div>
            </div>
            <div>
              <label class="mb-1 block text-sm font-semibold" for="phone">{{ $t('marketing.register.phone') }}</label>
              <input
                id="phone"
                v-model="form.phone"
                placeholder="07xxxxxxxx"
                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-semibold" for="tin">{{ $t('marketing.register.tin') }}</label>
              <input
                id="tin"
                v-model="form.tin"
                placeholder="9–10 digits"
                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-semibold" for="city">{{ $t('marketing.register.city') }}</label>
              <input
                id="city"
                v-model="form.city"
                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-semibold" for="address">{{ $t('marketing.register.address') }}</label>
              <input
                id="address"
                v-model="form.address"
                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/30"
              />
            </div>
          </div>

          <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>

          <div class="mt-5">
            <p class="text-sm font-semibold text-ink">{{ $t('marketing.register.heardAbout') }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $t('heardAbout.platformHint') }}</p>
            <HeardAboutChips
              class="mt-2"
              :options="PLATFORM_HEARD_ABOUT"
              label-key="heardAbout.platform"
              v-model="form.heardAboutUs"
            />
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="mt-6 w-full rounded-full bg-forest py-3 text-sm font-bold text-white transition hover:bg-forest-mid disabled:opacity-60"
          >
            {{ loading ? $t('marketing.register.creating') : $t('marketing.register.submit') }}
          </button>

          <p class="mt-4 text-center text-sm text-ink-muted">
            {{ $t('marketing.register.already') }}
            <NuxtLink to="/staff/login" class="font-semibold text-brand hover:underline">{{ $t('marketing.register.signIn') }}</NuxtLink>
          </p>
        </form>

        <aside class="rounded-2xl border border-line bg-sand p-6 text-ink sm:p-8">
          <h2 class="font-display text-xl font-bold text-forest">{{ $t('marketing.register.included') }}</h2>
          <ul class="mt-5 space-y-3 text-sm leading-relaxed text-ink/90">
            <li>{{ $t('marketing.register.i1') }}</li>
            <li>{{ $t('marketing.register.i2') }}</li>
            <li>{{ $t('marketing.register.i3') }}</li>
            <li>{{ $t('marketing.register.i4') }}</li>
            <li>{{ $t('marketing.register.i5') }}</li>
            <li>{{ $t('marketing.register.i6') }}</li>
          </ul>
          <p class="mt-8 text-xs text-ink/70">
            {{ $t('marketing.register.needHelp') }}
            <a class="font-semibold underline" href="mailto:info@inovasiyo.rw">info@inovasiyo.rw</a>
            or call +250 781 612 134.
          </p>
        </aside>
      </div>
    </div>
  </div>
</template>
