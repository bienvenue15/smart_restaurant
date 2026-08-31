<script setup lang="ts">
import type { MenuItem } from '~/types/menu';

const props = defineProps<{ item: MenuItem }>();
const emit = defineEmits<{ add: [item: MenuItem] }>();

const priceLabel = computed(() => `${Number(props.item.price).toLocaleString()} RWF`);

const runtimeConfig = useRuntimeConfig();
const imageSrc = computed(() => resolveMediaUrl(props.item.imageUrl, runtimeConfig.public.apiBaseUrl as string));
</script>

<template>
  <div class="mb-2.5 flex items-center gap-3 rounded-2xl border border-line bg-white p-3">
    <img
      v-if="imageSrc"
      :src="imageSrc"
      alt=""
      class="h-14 w-14 shrink-0 rounded-[14px] object-cover"
    />
    <div
      v-else
      class="grid h-14 w-14 shrink-0 place-items-center rounded-[14px] bg-sand text-2xl"
    >
      🍽
    </div>
    <div class="min-w-0 flex-1">
      <h4 class="text-sm font-bold text-ink">
        {{ item.name }}
      </h4>
      <p v-if="item.description" class="mt-0.5 truncate text-[11.5px] text-ink-muted">{{ item.description }}</p>
      <span
        v-if="item.isSpecial"
        class="mt-1 inline-block rounded-full bg-mint px-2 py-0.5 text-[9.5px] font-extrabold text-forest-mid"
      >
        {{ $t('customer.special') }}
      </span>
    </div>
    <div class="ml-auto shrink-0 text-right">
      <b class="mb-1.5 block text-[13px] text-forest">{{ priceLabel }}</b>
      <button
        v-if="item.isAvailable"
        type="button"
        class="ml-auto grid h-7 w-7 place-items-center rounded-[9px] bg-brand text-[17px] font-extrabold text-white"
        @click="emit('add', item)"
      >
        +
      </button>
      <span v-else class="text-[11px] text-ink-muted">{{ $t('customer.unavailable') }}</span>
    </div>
  </div>
</template>
