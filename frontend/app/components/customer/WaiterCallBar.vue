<script setup lang="ts">
const emit = defineEmits<{
  call: [requestType: 'ASSISTANCE' | 'BILL'];
}>();

const sending = ref<'ASSISTANCE' | 'BILL' | null>(null);
const sent = ref<'ASSISTANCE' | 'BILL' | null>(null);

async function trigger(requestType: 'ASSISTANCE' | 'BILL') {
  if (sending.value) return;
  sending.value = requestType;
  try {
    emit('call', requestType);
    sent.value = requestType;
    setTimeout(() => {
      if (sent.value === requestType) sent.value = null;
    }, 4000);
  } finally {
    sending.value = null;
  }
}
</script>

<template>
  <div class="fixed bottom-4 left-4 flex flex-col gap-2">
    <button
      class="rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-lg disabled:opacity-50"
      :disabled="sending === 'ASSISTANCE'"
      @click="trigger('ASSISTANCE')"
    >
      {{ sent === 'ASSISTANCE' ? $t('customer.waiterOnTheWay') : $t('customer.callWaiter') }}
    </button>
    <button
      class="rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-lg disabled:opacity-50"
      :disabled="sending === 'BILL'"
      @click="trigger('BILL')"
    >
      {{ sent === 'BILL' ? $t('customer.billRequested') : $t('customer.requestBill') }}
    </button>
  </div>
</template>
