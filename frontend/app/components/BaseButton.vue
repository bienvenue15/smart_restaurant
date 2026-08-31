<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'danger' | 'dark';
    disabled?: boolean;
    type?: 'button' | 'submit';
  }>(),
  { variant: 'primary', disabled: false, type: 'button' },
);
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    class="inline-flex items-center justify-center rounded-full px-5 py-2.5 text-sm font-bold transition-colors disabled:cursor-not-allowed disabled:opacity-50"
    :class="{
      'bg-brand text-[#3a2a05] hover:bg-brand-dark hover:text-white': variant === 'primary',
      'border-[1.5px] border-forest bg-transparent text-forest hover:bg-forest hover:text-white': variant === 'secondary',
      'bg-clay text-white hover:bg-terra': variant === 'danger',
      'bg-forest text-white hover:bg-forest-mid': variant === 'dark',
    }"
  >
    <slot />
  </button>
</template>
