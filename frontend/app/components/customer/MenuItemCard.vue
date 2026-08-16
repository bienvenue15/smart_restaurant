<script setup lang="ts">
import type { MenuItem } from '~/types/menu';

const props = defineProps<{ item: MenuItem }>();
const emit = defineEmits<{ add: [item: MenuItem] }>();

const priceLabel = computed(() => Number(props.item.price).toLocaleString());
</script>

<template>
  <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4">
    <div class="min-w-0">
      <p class="font-medium text-gray-900">
        {{ item.name }}
        <span v-if="item.isSpecial" class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700">Special</span>
      </p>
      <p v-if="item.description" class="mt-0.5 truncate text-sm text-gray-500">{{ item.description }}</p>
      <p class="mt-1 text-sm font-semibold text-gray-900">{{ priceLabel }}</p>
    </div>

    <BaseButton v-if="item.isAvailable" @click="emit('add', item)">Add</BaseButton>
    <span v-else class="whitespace-nowrap self-center text-sm text-gray-400">Unavailable</span>
  </div>
</template>
