<script setup lang="ts">
definePageMeta({ layout: 'marketing' });

const { t } = useI18n();

useSeoMeta({
  title: 'FAQ — Smart Restaurant',
  description: 'Everything owners and managers ask before switching to Smart Restaurant.',
});

const groups = computed(() => [
  {
    title: t('marketing.faq.g1'),
    items: [
      { q: t('marketing.faq.q1'), a: t('marketing.faq.q1a') },
      { q: t('marketing.faq.q2'), a: t('marketing.faq.q2a') },
      { q: t('marketing.faq.q3'), a: t('marketing.faq.q3a') },
    ],
  },
  {
    title: t('marketing.faq.g2'),
    items: [
      { q: t('marketing.faq.q4'), a: t('marketing.faq.q4a') },
      { q: t('marketing.faq.q5'), a: t('marketing.faq.q5a') },
      { q: t('marketing.faq.q6'), a: t('marketing.faq.q6a') },
    ],
  },
  {
    title: t('marketing.faq.g3'),
    items: [
      { q: t('marketing.faq.q7'), a: t('marketing.faq.q7a') },
      { q: t('marketing.faq.q8'), a: t('marketing.faq.q8a') },
    ],
  },
  {
    title: t('marketing.faq.g4'),
    items: [
      { q: t('marketing.faq.q9'), a: t('marketing.faq.q9a') },
      { q: t('marketing.faq.q10'), a: t('marketing.faq.q10a') },
    ],
  },
]);
</script>

<template>
  <div class="bg-diamonds">
    <header class="px-6 pb-2.5 pt-11 text-center">
      <span class="mb-4 inline-block rounded-full bg-clay px-4 py-2 text-xs font-bold uppercase tracking-[0.1em] text-[#fbeee4]">FAQ</span>
      <h1 class="font-display text-[clamp(28px,4vw,44px)] text-forest">{{ $t('marketing.faq.h1') }}</h1>
      <p class="mx-auto mt-2.5 max-w-[560px] text-base text-ink-muted">{{ $t('marketing.faq.sub') }}</p>
    </header>

    <main class="mx-auto max-w-[760px] px-6 pb-8 pt-6">
      <section v-for="(group, gi) in groups" :key="group.title" :class="gi === 0 ? '' : 'mt-6'">
        <h2 class="mb-3 font-display text-[19px] text-clay">{{ group.title }}</h2>
        <details
          v-for="(item, ii) in group.items"
          :key="item.q"
          class="mb-2.5 rounded-[14px] border border-line bg-card px-5 py-4 open:border-brand"
          :open="gi === 0 && ii === 0"
        >
          <summary class="cursor-pointer text-[15px] font-bold text-forest">{{ item.q }}</summary>
          <p class="mt-2 text-sm text-ink-muted">{{ item.a }}</p>
        </details>
      </section>
    </main>

    <section class="mx-auto mb-12 max-w-[760px] px-6">
      <div class="flex flex-wrap items-center justify-between gap-4 rounded-[18px] bg-forest p-7 text-white">
        <div>
          <h2 class="font-display text-xl">{{ $t('marketing.faq.ctaTitle') }}</h2>
          <p class="mt-1 text-[13.5px] text-[#bcd6ca]">{{ $t('marketing.faq.ctaBody') }}</p>
        </div>
        <NuxtLink
          to="/register"
          class="rounded-full bg-brand px-6 py-3 text-[15px] font-bold text-[#3a2a05]"
        >
          {{ $t('marketing.faq.cta') }}
        </NuxtLink>
      </div>
    </section>
  </div>
</template>
