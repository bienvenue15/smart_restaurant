<script setup lang="ts">
definePageMeta({ layout: 'marketing' });

useSeoMeta({
  title: 'Privacy Policy — Smart Restaurant',
  description: 'How Smart Restaurant by Inovasiyo Ltd collects and uses personal data.',
});
</script>

<template>
  <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
    <h1 class="font-display text-3xl font-bold text-ink">{{ $t('legal.privacyTitle') }}</h1>
    <p class="mt-2 text-sm text-ink-muted">{{ $t('legal.updated') }}</p>
    <div class="mt-8 space-y-4 text-sm leading-relaxed text-ink">
      <p>{{ $t('legal.privacy1') }}</p>
      <p>{{ $t('legal.privacy2') }}</p>
      <p>{{ $t('legal.privacy3') }}</p>
      <p>{{ $t('legal.privacy4') }}</p>
    </div>
  </article>
</template>
