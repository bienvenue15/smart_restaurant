<script setup lang="ts">
withDefaults(
  defineProps<{
    size?: number;
    tone?: 'forest' | 'onDark';
  }>(),
  { size: 34, tone: 'forest' },
);
</script>

<template>
  <span
    class="inline-grid shrink-0 place-items-center"
    :class="tone === 'onDark' ? 'bg-white/15' : 'bg-forest'"
    :style="{
      width: `${size}px`,
      height: `${size}px`,
      borderRadius: `${Math.round(size * 0.26)}px`,
    }"
  >
    <svg
      :width="Math.round(size * 0.5)"
      :height="Math.round(size * 0.5)"
      viewBox="0 0 16 16"
      aria-hidden="true"
    >
      <path
        fill="#fff"
        d="M0 0h6v6H0zM10 0h6v6h-6zM0 10h6v6H0zM2 2h2v2H2zM12 2h2v2h-2zM2 12h2v2H2zM8 8h2v2H8zM12 8h2v2h-2zM8 12h2v2H8zM12 12h4v4h-4z"
      />
    </svg>
  </span>
</template>
