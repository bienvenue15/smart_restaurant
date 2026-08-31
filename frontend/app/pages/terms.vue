<script setup lang="ts">
definePageMeta({ layout: 'marketing' });

useSeoMeta({
  title: 'Terms of Service — Smart Restaurant',
  description: 'Terms for using Smart Restaurant by Inovasiyo Ltd.',
});
</script>

<template>
  <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
    <h1 class="font-display text-3xl font-bold text-ink">{{ $t('legal.termsTitle') }}</h1>
    <p class="mt-2 text-sm text-ink-muted">{{ $t('legal.updated') }}</p>
    <div class="mt-8 space-y-4 text-sm leading-relaxed text-ink">
      <p>{{ $t('legal.terms1') }}</p>
      <p>{{ $t('legal.terms2') }}</p>
      <p>{{ $t('legal.terms3') }}</p>
      <p>{{ $t('legal.terms4') }}</p>
    </div>
  </article>
</template>
