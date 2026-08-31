<script setup lang="ts">
const open = defineModel<boolean>({ default: false });
const api = useApi();
const { t } = useI18n();

const name = ref('');
const email = ref('');
const restaurant = ref('');
const subject = ref('');
const message = ref('');
const status = ref<'idle' | 'sent'>('idle');
const sending = ref(false);
const error = ref<string | null>(null);

function close() {
  open.value = false;
  status.value = 'idle';
  error.value = null;
}

async function submit() {
  sending.value = true;
  error.value = null;
  try {
    await api.post('/support/messages', {
      contactName: name.value,
      contactEmail: email.value,
      restaurantName: restaurant.value || undefined,
      subject: subject.value,
      message: message.value,
    });
    status.value = 'sent';
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('marketing.contact.failed');
  } finally {
    sending.value = false;
  }
}

watch(open, (value) => {
  if (!value) {
    name.value = '';
    email.value = '';
    restaurant.value = '';
    subject.value = '';
    message.value = '';
    status.value = 'idle';
    error.value = null;
  }
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[60] flex items-end justify-center bg-black/60 p-4 sm:items-center"
      @click.self="close"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="contact-title"
        class="w-full max-w-lg rounded-2xl border border-line bg-card p-6 shadow-brand sm:p-8"
      >
        <div class="mb-5 flex items-start justify-between gap-4">
          <div>
            <h2 id="contact-title" class="font-display text-2xl font-bold text-forest">{{ $t('marketing.contact.title') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ $t('marketing.contact.subtitle') }}</p>
          </div>
          <button
            type="button"
            class="rounded-lg p-2 text-ink-muted transition hover:bg-black/5 hover:text-ink"
            aria-label="Close"
            @click="close"
          >
            ✕
          </button>
        </div>

        <p v-if="error" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>
        <p v-if="status === 'sent'" class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
          {{ $t('marketing.contact.sent') }}
        </p>

        <form v-if="status !== 'sent'" class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="mb-1 block text-sm font-semibold text-ink" for="contact-name">{{ $t('marketing.contact.name') }}</label>
            <input
              id="contact-name"
              v-model="name"
              required
              class="staff-input"
              :placeholder="$t('marketing.contact.placeholderName')"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-ink" for="contact-email">{{ $t('marketing.contact.email') }}</label>
            <input
              id="contact-email"
              v-model="email"
              type="email"
              required
              class="staff-input"
              :placeholder="$t('marketing.contact.placeholderEmail')"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-ink" for="contact-restaurant">{{ $t('marketing.contact.restaurant') }}</label>
            <input
              id="contact-restaurant"
              v-model="restaurant"
              class="staff-input"
              :placeholder="$t('marketing.contact.optional')"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-ink" for="contact-subject">{{ $t('marketing.contact.subject') }}</label>
            <input
              id="contact-subject"
              v-model="subject"
              required
              class="staff-input"
              :placeholder="$t('marketing.contact.placeholderSubject')"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-ink" for="contact-message">{{ $t('marketing.contact.message') }}</label>
            <textarea
              id="contact-message"
              v-model="message"
              required
              rows="4"
              class="staff-input"
              :placeholder="$t('marketing.contact.placeholderMessage')"
            />
          </div>
          <button
            type="submit"
            :disabled="sending"
            class="w-full rounded-full bg-brand py-3 text-sm font-bold text-[#3a2a05] transition hover:bg-brand-dark hover:text-white disabled:opacity-50"
          >
            {{ sending ? $t('marketing.contact.sending') : $t('marketing.contact.send') }}
          </button>
        </form>
      </div>
    </div>
  </Teleport>
</template>
