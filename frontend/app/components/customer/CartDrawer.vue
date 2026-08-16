<script setup lang="ts">
import type { CartLine } from '~/types/menu';

const props = defineProps<{
  open: boolean;
  cart: CartLine[];
  total: number;
  placing: boolean;
}>();

const emit = defineEmits<{
  close: [];
  updateQuantity: [menuItemId: string, quantity: number];
  placeOrder: [];
}>();
</script>

<template>
  <Teleport to="body">
    <div v-if="props.open" class="fixed inset-0 z-50 flex justify-end bg-black/30" @click.self="emit('close')">
      <div class="flex h-full w-full max-w-sm flex-col bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 p-4">
          <h2 class="text-lg font-semibold text-gray-900">Your order</h2>
          <button class="text-gray-400 hover:text-gray-600" @click="emit('close')">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto p-4">
          <p v-if="props.cart.length === 0" class="text-sm text-gray-500">Your cart is empty.</p>

          <div v-for="line in props.cart" :key="line.menuItemId" class="mb-3 flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-gray-900">{{ line.name }}</p>
              <p class="text-xs text-gray-500">{{ line.price.toLocaleString() }} × {{ line.quantity }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                class="h-7 w-7 rounded border border-gray-300 text-sm"
                @click="emit('updateQuantity', line.menuItemId, line.quantity - 1)"
              >
                −
              </button>
              <span class="w-4 text-center text-sm">{{ line.quantity }}</span>
              <button
                class="h-7 w-7 rounded border border-gray-300 text-sm"
                @click="emit('updateQuantity', line.menuItemId, line.quantity + 1)"
              >
                +
              </button>
            </div>
          </div>
        </div>

        <div class="border-t border-gray-200 p-4">
          <div class="mb-3 flex items-center justify-between text-sm font-semibold text-gray-900">
            <span>Total</span>
            <span>{{ props.total.toLocaleString() }}</span>
          </div>
          <BaseButton class="w-full" :disabled="props.cart.length === 0 || props.placing" @click="emit('placeOrder')">
            {{ props.placing ? 'Placing order…' : 'Place order' }}
          </BaseButton>
        </div>
      </div>
    </div>
  </Teleport>
</template>
