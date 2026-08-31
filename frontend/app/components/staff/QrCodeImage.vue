<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    value: string;
    size?: number;
    alt?: string;
  }>(),
  { size: 176, alt: '' },
);

const dataUrl = useQrDataUrl(() => props.value, () => props.size);
</script>

<template>
  <div class="aspect-square w-full bg-white" :style="{ minHeight: `${size}px` }">
    <img
      v-if="dataUrl"
      :src="dataUrl"
      :alt="alt"
      :width="size"
      :height="size"
      class="block h-auto w-full bg-white"
    />
  </div>
</template>
