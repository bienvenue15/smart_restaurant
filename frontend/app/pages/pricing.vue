<script setup lang="ts">
definePageMeta({ layout: 'marketing' });

const { t } = useI18n();
const yearly = ref(false);

useSeoMeta({
  title: 'Pricing — Smart Restaurant',
  description: 'Simple pricing that grows with you. Every plan starts with a 30-day free trial.',
});

const plans = computed(() => [
  {
    name: t('marketing.pricing.starter'),
    for: t('marketing.pricing.starterFor'),
    monthly: '25,000',
    yearly: '250,000',
    custom: false,
    hot: false,
    features: [
      { ok: true, text: t('marketing.pricing.trialIncl') },
      { ok: true, text: t('marketing.pricing.t10') },
      { ok: true, text: t('marketing.pricing.i50') },
      { ok: true, text: t('marketing.pricing.qr') },
      { ok: true, text: t('marketing.pricing.kitchen') },
      { ok: true, text: t('marketing.pricing.cash') },
      { ok: false, text: t('marketing.pricing.staffTrack') },
      { ok: false, text: t('marketing.pricing.advanced') },
    ],
    cta: t('marketing.pricing.choose'),
    to: '/register',
    variant: 'ghost' as const,
  },
  {
    name: t('marketing.pricing.pro'),
    for: t('marketing.pricing.proFor'),
    monthly: '45,000',
    yearly: '450,000',
    custom: false,
    hot: true,
    features: [
      { ok: true, text: t('marketing.pricing.trialIncl') },
      { ok: true, text: t('marketing.pricing.t40') },
      { ok: true, text: t('marketing.pricing.i200') },
      { ok: true, text: t('marketing.pricing.everythingStarter') },
      { ok: true, text: t('marketing.pricing.waiterCall') },
      { ok: true, text: t('marketing.pricing.staffTrack') },
      { ok: true, text: t('marketing.pricing.liveSales') },
      { ok: true, text: t('marketing.pricing.langs') },
    ],
    cta: t('marketing.pricing.choose'),
    to: '/register',
    variant: 'primary' as const,
  },
  {
    name: t('marketing.pricing.enterprise'),
    for: t('marketing.pricing.enterpriseFor'),
    monthly: '',
    yearly: '',
    custom: true,
    hot: false,
    features: [
      { ok: true, text: t('marketing.pricing.unlimited') },
      { ok: true, text: t('marketing.pricing.unlimitedItems') },
      { ok: true, text: t('marketing.pricing.everythingPro') },
      { ok: true, text: t('marketing.pricing.superadmin') },
      { ok: true, text: t('marketing.pricing.branding') },
      { ok: true, text: t('marketing.pricing.api') },
      { ok: true, text: t('marketing.pricing.dedicated') },
    ],
    cta: t('marketing.pricing.contact'),
    to: '',
    variant: 'dark' as const,
  },
]);

const extras = computed(() => [
  { title: t('marketing.pricing.i1'), body: t('marketing.pricing.i1d') },
  { title: t('marketing.pricing.i2'), body: t('marketing.pricing.i2d') },
  { title: t('marketing.pricing.i3'), body: t('marketing.pricing.i3d') },
  { title: t('marketing.pricing.i4'), body: t('marketing.pricing.i4d') },
]);
</script>

<template>
  <div class="bg-diamonds">
    <header class="px-6 pb-2 pt-11 text-center">
      <span class="mb-4 inline-block rounded-full bg-clay px-4 py-2 text-xs font-bold uppercase tracking-[0.1em] text-[#fbeee4]">
        {{ $t('marketing.pricing.tag') }}
      </span>
      <h1 class="font-display text-[clamp(28px,4vw,44px)] text-forest">{{ $t('marketing.pricing.h1') }}</h1>
      <p class="mx-auto mt-2.5 max-w-[560px] text-base text-ink-muted">{{ $t('marketing.pricing.sub') }}</p>
    </header>

    <div class="mx-auto max-w-[1140px] px-4 sm:px-6">
      <div class="mb-8 mt-6 flex flex-wrap items-center justify-center gap-2">
        <div class="flex rounded-full border border-line bg-sand p-1">
          <button
            type="button"
            class="rounded-full px-5 py-2 text-[13.5px] font-extrabold"
            :class="!yearly ? 'bg-forest text-white' : 'text-ink-muted'"
            @click="yearly = false"
          >
            {{ $t('marketing.pricing.monthly') }}
          </button>
          <button
            type="button"
            class="rounded-full px-5 py-2 text-[13.5px] font-extrabold"
            :class="yearly ? 'bg-forest text-white' : 'text-ink-muted'"
            @click="yearly = true"
          >
            {{ $t('marketing.pricing.yearly') }}
          </button>
        </div>
        <span class="rounded-full bg-[#f6ded2] px-3 py-1 text-[11.5px] font-extrabold text-clay">
          {{ $t('marketing.pricing.save') }}
        </span>
      </div>

      <div class="grid gap-5 pb-6 lg:grid-cols-3">
        <article
          v-for="plan in plans"
          :key="plan.name"
          class="relative flex flex-col rounded-[20px] border bg-card px-6 py-8 shadow-brand"
          :class="plan.hot ? 'border-2 border-brand lg:-translate-y-2' : 'border-line'"
        >
          <span
            v-if="plan.hot"
            class="absolute -top-3.5 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-clay px-4 py-1 text-[11.5px] font-extrabold text-white"
          >
            {{ $t('marketing.pricing.pop') }}
          </span>
          <h2 class="font-display text-[21px] text-forest">{{ plan.name }}</h2>
          <p class="mb-4 mt-1 min-h-[38px] text-[13px] text-ink-muted">{{ plan.for }}</p>
          <div class="font-display text-4xl font-bold text-clay">
            <template v-if="plan.custom">{{ $t('marketing.pricing.custom') }}</template>
            <template v-else>
              {{ yearly ? plan.yearly : plan.monthly }}
              <small class="font-body text-sm font-semibold text-ink-muted">RWF</small>
            </template>
          </div>
          <div class="mb-5 text-xs text-ink-muted">
            {{ plan.custom ? $t('marketing.pricing.per3') : yearly ? $t('marketing.pricing.perY') : $t('marketing.pricing.perM') }}
          </div>
          <ul class="mb-6 grid gap-2.5">
            <li
              v-for="feat in plan.features"
              :key="feat.text"
              class="flex items-start gap-2.5 text-[13.5px]"
              :class="feat.ok ? '' : 'text-[#b5ab97]'"
            >
              <span
                class="mt-0.5 grid h-[19px] w-[19px] shrink-0 place-items-center rounded-full text-[11px] font-extrabold"
                :class="feat.ok ? 'bg-mint text-forest-mid' : 'bg-[#f0ead8] text-[#b5ab97]'"
              >
                {{ feat.ok ? '✓' : '–' }}
              </span>
              {{ feat.text }}
            </li>
          </ul>
          <NuxtLink
            v-if="plan.to"
            :to="plan.to"
            class="mt-auto inline-flex justify-center rounded-full px-6 py-3 text-center text-[15px] font-bold"
            :class="
              plan.variant === 'primary'
                ? 'bg-brand text-[#3a2a05]'
                : plan.variant === 'dark'
                  ? 'bg-forest text-white'
                  : 'border-[1.5px] border-forest text-forest'
            "
          >
            {{ plan.cta }}
          </NuxtLink>
          <a
            v-else
            href="mailto:info@inovasiyo.rw"
            class="mt-auto inline-flex justify-center rounded-full bg-forest px-6 py-3 text-center text-[15px] font-bold text-white"
          >
            {{ plan.cta }}
          </a>
        </article>
      </div>

      <div class="mb-10 mt-3 grid gap-4 rounded-[18px] border border-line bg-sand p-6 sm:grid-cols-2 lg:grid-cols-4">
        <h3 class="font-display text-[17px] text-forest sm:col-span-2 lg:col-span-4">{{ $t('marketing.pricing.inc') }}</h3>
        <div v-for="extra in extras" :key="extra.title">
          <b class="mb-1 block text-sm text-forest">{{ extra.title }}</b>
          <span class="text-[12.5px] text-ink-muted">{{ extra.body }}</span>
        </div>
      </div>

      <section class="mb-10">
        <h2 class="text-center font-display text-[26px] text-forest">{{ $t('marketing.pricing.hwTitle') }}</h2>
        <p class="mb-6 text-center text-sm text-ink-muted">{{ $t('marketing.pricing.hwSub') }}</p>
        <div class="grid gap-4 md:grid-cols-3">
          <article class="rounded-[18px] border border-line bg-card p-6 text-center shadow-brand">
            <div class="mx-auto w-[92px]">
              <div class="rounded-t-[9px] px-2 pb-1.5 pt-2" style="background: linear-gradient(160deg, #cf9354, #b4793b); box-shadow: inset 0 0 0 2px rgba(90,50,10,.22)">
                <small class="mb-1 block text-[8px] font-extrabold tracking-wider text-[#4a2c0d]">SCAN ME</small>
                <span class="mx-auto grid h-[42px] w-[42px] place-items-center rounded-md bg-white">
                  <BrandMark :size="26" />
                </span>
              </div>
              <div class="rounded-b-md bg-[#a06a33] py-1 text-xs tracking-widest">🧂🌶️🥢</div>
            </div>
            <h3 class="mb-1.5 mt-3 font-body text-[16.5px] font-bold text-forest">{{ $t('marketing.pricing.hw1t') }}</h3>
            <p class="min-h-[58px] text-[13px] text-ink-muted">{{ $t('marketing.pricing.hw1d') }}</p>
            <div class="mt-3 font-display text-2xl font-bold text-clay">12,000 RWF</div>
            <div class="text-[11.5px] text-ink-muted">{{ $t('marketing.pricing.hwpt') }}</div>
          </article>
          <article class="rounded-[18px] border border-line bg-card p-6 text-center shadow-brand">
            <div class="mx-auto w-[92px]">
              <div class="rounded-t-[9px] px-2 pb-1.5 pt-2" style="background: linear-gradient(160deg, #fbfdff, #dde5ec); box-shadow: inset 0 0 0 2px rgba(60,80,100,.18)">
                <small class="mb-1 block text-[8px] font-extrabold tracking-wider text-[#38505f]">SCAN ME</small>
                <span class="mx-auto grid h-[42px] w-[42px] place-items-center rounded-md bg-white">
                  <BrandMark :size="26" />
                </span>
              </div>
              <div class="rounded-b-md bg-[#c3cdd6] py-1 text-xs tracking-widest">🧂🌶️</div>
            </div>
            <h3 class="mb-1.5 mt-3 font-body text-[16.5px] font-bold text-forest">{{ $t('marketing.pricing.hw2t') }}</h3>
            <p class="min-h-[58px] text-[13px] text-ink-muted">{{ $t('marketing.pricing.hw2d') }}</p>
            <div class="mt-3 font-display text-2xl font-bold text-clay">5,000 RWF</div>
            <div class="text-[11.5px] text-ink-muted">{{ $t('marketing.pricing.hwpt') }}</div>
          </article>
          <article class="rounded-[18px] border border-line bg-card p-6 text-center shadow-brand">
            <div class="mx-auto w-[92px]">
              <div class="rounded-t-[9px] px-2 pb-1.5 pt-2" style="background: linear-gradient(160deg, #e8f3ec, #cfe4d7); box-shadow: inset 0 0 0 2px rgba(13,59,46,.15)">
                <small class="mb-1 block text-[8px] font-extrabold tracking-wider text-forest">SCAN ME</small>
                <span class="mx-auto grid h-[42px] w-[42px] place-items-center rounded-md bg-white">
                  <BrandMark :size="26" />
                </span>
              </div>
              <div class="py-1 text-[10px] text-ink-muted">{{ $t('marketing.pricing.hw3b') }}</div>
            </div>
            <h3 class="mb-1.5 mt-3 font-body text-[16.5px] font-bold text-forest">{{ $t('marketing.pricing.hw3t') }}</h3>
            <p class="min-h-[58px] text-[13px] text-ink-muted">{{ $t('marketing.pricing.hw3d') }}</p>
            <div class="mt-3 font-display text-2xl font-bold text-clay">{{ $t('marketing.pricing.hw3p') }}</div>
            <div class="text-[11.5px] text-ink-muted">{{ $t('marketing.pricing.hwpt3') }}</div>
          </article>
        </div>
      </section>
    </div>

  </div>
</template>
