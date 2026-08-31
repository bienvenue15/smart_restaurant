<script setup lang="ts">
const open = defineModel<boolean>({ default: false });
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-end justify-center bg-black/45 p-4 sm:items-center"
      @click.self="open = false"
    >
      <div class="w-full max-w-md rounded-2xl border border-line bg-card p-5 shadow-brand">
        <slot />
      </div>
    </div>
  </Teleport>
</template>
