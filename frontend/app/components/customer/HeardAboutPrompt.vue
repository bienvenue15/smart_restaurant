<script setup lang="ts">
import type { OrderSummary } from '~/types/menu';
import type { GuestHeardAboutChannel } from '~/utils/heardAbout';

const props = defineProps<{ orders: OrderSummary[] }>();

const api = useApi();
const { t } = useI18n();
const session = useCustomerSession();
const busy = ref(false);
const done = ref(false);

const promptOrder = computed(
  () =>
    props.orders.find((order) => order.paymentStatus === 'PAID' && !order.guestHeardAbout) ?? null,
);

const open = computed({
  get: () => Boolean(promptOrder.value) && !done.value,
  set: (value) => {
    if (!value && promptOrder.value && !busy.value) {
      void submit({ skipped: true, rating: null, channel: null, comment: null });
    }
  },
});

async function submit(payload: { skipped: boolean; rating: number | null; channel: GuestHeardAboutChannel | null; comment: string | null }) {
  if (!promptOrder.value || busy.value) return;
  busy.value = true;
  try {
    await api.post(`/customer/orders/${promptOrder.value.id}/heard-about`, payload, {
      successMessage: t('heardAbout.thanks'),
    });
    done.value = true;
    session.clear();
    await navigateTo('/');
  } catch {
    // Keep the prompt visible so they can retry; failure popup comes from useApi.
  } finally {
    busy.value = false;
  }
}

watch(
  () => promptOrder.value?.id,
  () => {
    done.value = false;
  },
);
</script>

<template>
  <GuestFeedbackModal
    v-model="open"
    :title="$t('heardAbout.feedbackTitle')"
    :body="$t('heardAbout.feedbackHint')"
    :busy="busy"
    @submit="submit"
  />
</template>
