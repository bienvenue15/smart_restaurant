<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string;
    body?: string;
  }>(),
  { title: undefined, body: undefined },
);
</script>

<template>
  <div class="rounded-2xl border border-dashed border-line bg-card px-6 py-10 text-center">
    <p v-if="title" class="font-display font-bold text-forest">{{ title }}</p>
    <p v-if="body" class="mt-1 text-sm text-ink-muted">{{ body }}</p>
    <div v-if="$slots.default" class="mt-4">
      <slot />
    </div>
  </div>
</template>
