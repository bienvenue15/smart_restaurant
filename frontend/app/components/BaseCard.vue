<script setup lang="ts">
defineProps<{
  padding?: boolean;
}>();
</script>

<template>
  <div class="staff-card" :class="{ 'p-0 overflow-hidden': padding === false }">
    <slot />
  </div>
</template>
