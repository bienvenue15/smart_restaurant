<script setup lang="ts">
definePageMeta({ layout: 'marketing' });

const { t } = useI18n();

useSeoMeta({
  title: 'Smart Restaurant — Your café or restaurant, made super easy',
  description:
    'Guests scan a QR code, order in seconds, and your kitchen gets it instantly. Menus, orders, staff and payments — one simple dashboard.',
});

const features = computed(() => [
  { icon: '📱', title: t('marketing.features.qr.title'), body: t('marketing.features.qr.body') },
  { icon: '🍳', title: t('marketing.features.kitchen.title'), body: t('marketing.features.kitchen.body') },
  { icon: '💳', title: t('marketing.features.pay.title'), body: t('marketing.features.pay.body') },
  { icon: '✏️', title: t('marketing.features.menu.title'), body: t('marketing.features.menu.body') },
  { icon: '🔔', title: t('marketing.features.staff.title'), body: t('marketing.features.staff.body') },
  { icon: '📊', title: t('marketing.features.analytics.title'), body: t('marketing.features.analytics.body') },
]);

const steps = computed(() => [
  { n: '1', title: t('marketing.how.s1t'), body: t('marketing.how.s1b') },
  { n: '2', title: t('marketing.how.s2t'), body: t('marketing.how.s2b') },
  { n: '3', title: t('marketing.how.s3t'), body: t('marketing.how.s3b') },
  { n: '4', title: t('marketing.how.s4t'), body: t('marketing.how.s4b') },
]);

const stats = computed(() => [
  { value: '10+', label: t('marketing.network.restaurants') },
  { value: '15+', label: t('marketing.network.tables') },
  { value: '8–12 min', label: t('marketing.network.seatToFood') },
  { value: '24/7', label: t('marketing.network.support') },
]);
</script>

<template>
  <div>
    <section class="hero-diamonds">
      <div class="mx-auto grid max-w-[1140px] items-start gap-9 px-4 pb-14 pt-10 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:gap-9 lg:pb-16 lg:pt-11">
        <div class="animate-rise max-w-xl">
          <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-[#e0cba0] bg-sand px-3.5 py-1.5 text-[13px] font-bold text-clay">
            <b class="rounded-full bg-clay px-2 py-0.5 text-[11px] font-bold text-white">Murakaza neza!</b>
            {{ $t('marketing.hero.pill') }}
          </span>
          <h1 class="font-display text-[clamp(32px,4.6vw,52px)] font-bold leading-[1.07] text-forest">
            {{ $t('marketing.hero.claim') }}
            <em class="not-italic text-clay">{{ $t('marketing.hero.claimEm') }}</em>
          </h1>
          <p class="mt-4 max-w-[46ch] text-[17px] leading-relaxed text-ink-muted">
            {{ $t('marketing.hero.body') }}
          </p>
          <div class="mt-7 flex flex-wrap items-center gap-3">
            <NuxtLink
              to="/register"
              class="inline-flex rounded-full bg-forest px-6 py-3 text-[15px] font-bold text-white transition hover:bg-forest-mid"
            >
              {{ $t('marketing.hero.cta') }}
            </NuxtLink>
            <a
              href="#demo"
              class="inline-flex rounded-full border-[1.5px] border-forest px-6 py-3 text-[15px] font-bold text-forest transition hover:bg-forest hover:text-white"
            >
              ▶ {{ $t('marketing.hero.seeDemo') }}
            </a>
          </div>
          <div class="mt-7 flex flex-wrap gap-7">
            <div>
              <b class="block font-display text-2xl text-forest">60%</b>
              <span class="text-[13px] text-ink-muted">{{ $t('marketing.hero.p1') }}</span>
            </div>
            <div>
              <b class="block font-display text-2xl text-forest">+25%</b>
              <span class="text-[13px] text-ink-muted">{{ $t('marketing.hero.p2') }}</span>
            </div>
            <div>
              <b class="block font-display text-2xl text-forest">–80%</b>
              <span class="text-[13px] text-ink-muted">{{ $t('marketing.hero.p3') }}</span>
            </div>
          </div>
        </div>

        <div class="animate-rise relative flex items-start justify-center" style="animation-delay: 120ms">
          <div class="w-[min(215px,62vw)] rounded-[30px] bg-[#10241c] p-2 shadow-phone">
            <div class="overflow-hidden rounded-[23px] bg-[#fdfdfb]">
              <div class="grid h-[18px] place-items-center">
                <i class="block h-1.5 w-14 rounded-full bg-[#10241c]" />
              </div>
              <div class="flex items-center justify-between px-2.5 py-1.5 text-[9.5px] font-bold text-forest">
                <span>☰ &nbsp;Chez Karisimbi</span>
                <span class="rounded-full bg-mint px-2 py-0.5">{{ $t('marketing.hero.table') }}</span>
              </div>
              <div class="flex gap-1 overflow-hidden px-2.5 pb-2 text-[8.5px] font-bold">
                <span class="rounded-full border border-forest bg-forest px-2 py-0.5 text-white">{{ $t('marketing.hero.cAll') }}</span>
                <span class="rounded-full border border-line px-2 py-0.5 text-ink-muted">{{ $t('marketing.hero.cGrill') }}</span>
                <span class="rounded-full border border-line px-2 py-0.5 text-ink-muted">Pizza</span>
                <span class="rounded-full border border-line px-2 py-0.5 text-ink-muted">{{ $t('marketing.hero.cDrinks') }}</span>
              </div>
              <div class="mx-2.5 mb-1.5 flex items-center gap-1.5 rounded-xl border border-line bg-white p-1.5">
                <span class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-[9px] bg-[#fdeee2] text-[17px]">🍢</span>
                <div class="min-w-0">
                  <h5 class="truncate text-[9.5px] font-bold">{{ $t('marketing.hero.d1') }}</h5>
                  <p class="truncate text-[8px] text-ink-muted">{{ $t('marketing.hero.d1d') }}</p>
                </div>
                <div class="ml-auto shrink-0 text-right">
                  <b class="block text-[9.5px] text-forest">4,500</b>
                  <span class="ml-auto mt-0.5 grid h-[17px] w-[17px] place-items-center rounded-md bg-brand text-[11px] font-extrabold text-white">+</span>
                </div>
              </div>
              <div class="mx-2.5 mb-1.5 flex items-center gap-1.5 rounded-xl border border-line bg-white p-1.5">
                <span class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-[9px] bg-[#fdf6dd] text-[17px]">🍕</span>
                <div class="min-w-0">
                  <h5 class="truncate text-[9.5px] font-bold">Kigali Special Pizza</h5>
                  <p class="truncate text-[8px] text-ink-muted">{{ $t('marketing.hero.d2d') }}</p>
                </div>
                <div class="ml-auto shrink-0 text-right">
                  <b class="block text-[9.5px] text-forest">7,000</b>
                  <span class="ml-auto mt-0.5 grid h-[17px] w-[17px] place-items-center rounded-md bg-brand text-[11px] font-extrabold text-white">+</span>
                </div>
              </div>
              <div class="mx-2.5 mb-1.5 flex items-center gap-1.5 rounded-xl border border-line bg-white p-1.5">
                <span class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-[9px] bg-[#e7f3ea] text-[17px]">🥤</span>
                <div class="min-w-0">
                  <h5 class="truncate text-[9.5px] font-bold">{{ $t('marketing.hero.d3') }}</h5>
                  <p class="truncate text-[8px] text-ink-muted">{{ $t('marketing.hero.d3d') }}</p>
                </div>
                <div class="ml-auto shrink-0 text-right">
                  <b class="block text-[9.5px] text-forest">1,500</b>
                  <span class="ml-auto mt-0.5 grid h-[17px] w-[17px] place-items-center rounded-md bg-brand text-[11px] font-extrabold text-white">+</span>
                </div>
              </div>
              <div class="mx-2.5 mb-3 flex justify-between rounded-[11px] bg-forest px-3 py-2 text-[9.5px] font-bold text-white">
                <span>{{ $t('marketing.hero.cart') }}</span>
                <span>13,000 →</span>
              </div>
            </div>
          </div>
          <div class="absolute right-0 top-6 hidden rounded-xl border border-line bg-white px-3 py-2 text-[11px] font-bold text-forest shadow-brand sm:block">
            {{ $t('marketing.hero.f1') }}
          </div>
          <div class="absolute bottom-10 left-0 hidden items-center gap-2 rounded-xl border border-line bg-white px-3 py-2 text-[11px] font-bold text-forest shadow-brand sm:flex">
            <span class="h-2.5 w-2.5 rounded-full bg-emerald shadow-[0_0_0_4px_rgba(23,147,108,.18)]" />
            {{ $t('marketing.hero.f2') }}
          </div>
        </div>
      </div>
    </section>

    <section class="grid grid-cols-2 border-y border-line bg-sand md:grid-cols-4">
      <div
        v-for="(stat, i) in stats"
        :key="stat.label"
        class="px-4 py-6 text-center"
        :class="i > 0 ? 'border-l border-[#e0cfa8] max-md:odd:border-l-0' : ''"
      >
        <b class="block font-display text-[26px] text-clay">{{ stat.value }}</b>
        <span class="text-[13px] text-ink-muted">{{ stat.label }}</span>
      </div>
    </section>

    <section id="features" class="bg-[#fbf7ec] bg-diamonds py-16">
      <div class="mx-auto max-w-[1140px] px-4 sm:px-6">
        <h2 class="text-center font-display text-[clamp(24px,3vw,32px)] text-forest">{{ $t('marketing.features.title') }}</h2>
        <p class="mb-8 mt-2 text-center text-[15px] text-ink-muted">{{ $t('marketing.features.body') }}</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="(feature, index) in features"
            :key="feature.title"
            class="rounded-2xl border border-line bg-card p-6"
          >
            <span
              class="mb-3 grid h-11 w-11 place-items-center rounded-[13px] text-[21px]"
              :class="index % 3 === 1 ? 'bg-sand' : index % 3 === 2 ? 'bg-[#f6ded2]' : 'bg-mint'"
            >
              {{ feature.icon }}
            </span>
            <h3 class="mb-1.5 font-body text-base font-bold text-forest">{{ feature.title }}</h3>
            <p class="text-[13.5px] leading-relaxed text-ink-muted">{{ feature.body }}</p>
          </article>
        </div>
      </div>
    </section>

    <section id="how-it-works" class="py-16">
      <div class="mx-auto max-w-[1140px] px-4 sm:px-6">
        <h2 class="mb-8 text-center font-display text-[clamp(24px,3vw,32px)] text-forest">{{ $t('marketing.how.title') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="step in steps"
            :key="step.n"
            class="relative rounded-2xl border border-line bg-card p-[22px] text-center"
          >
            <span class="mx-auto mb-4 mt-1.5 grid h-[38px] w-[38px] place-items-center border-[3px] border-brand bg-clay font-extrabold text-white" style="transform: rotate(45deg)">
              <b style="transform: rotate(-45deg)">{{ step.n }}</b>
            </span>
            <h3 class="mb-1.5 font-body text-base font-bold text-forest">{{ step.title }}</h3>
            <p class="text-[13px] text-ink-muted">{{ step.body }}</p>
          </article>
        </div>
      </div>
    </section>

    <section id="demo" class="bg-forest text-white">
      <div class="mx-auto flex max-w-[1140px] flex-wrap items-center justify-between gap-5 px-4 py-10 sm:px-6">
        <div>
          <h2 class="font-display text-[clamp(20px,2.6vw,26px)]">{{ $t('marketing.demo.title') }}</h2>
          <p class="mt-1 text-sm text-[#bcd6ca]">{{ $t('marketing.demo.body') }}</p>
        </div>
        <NuxtLink
          to="/register"
          class="inline-flex rounded-full bg-brand px-6 py-3 text-[15px] font-bold text-[#3a2a05] transition hover:bg-brand-dark hover:text-white"
        >
          {{ $t('marketing.demo.cta') }}
        </NuxtLink>
      </div>
    </section>
  </div>
</template>
