<script setup lang="ts">
import { GUEST_HEARD_ABOUT, type GuestHeardAboutChannel } from '~/utils/heardAbout';

const COMMENT_MAX = 500;

const open = defineModel<boolean>({ default: false });

defineProps<{
  title: string;
  body: string;
  busy?: boolean;
}>();

const emit = defineEmits<{
  submit: [{ skipped: boolean; rating: number | null; channel: GuestHeardAboutChannel | null; comment: string | null }];
}>();

const rating = ref<number | null>(null);
const channel = ref<GuestHeardAboutChannel | null>(null);
const comment = ref('');

watch(open, (value) => {
  if (value) {
    rating.value = null;
    channel.value = null;
    comment.value = '';
  }
});

function skip() {
  emit('submit', { skipped: true, rating: null, channel: null, comment: null });
}

function send() {
  if (!rating.value) return;
  const note = comment.value.trim();
  emit('submit', {
    skipped: false,
    rating: rating.value,
    channel: channel.value,
    comment: note || null,
  });
}
</script>

<template>
  <BaseModal v-model="open">
    <p class="font-display text-lg font-bold text-forest">{{ title }}</p>
    <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ body }}</p>

    <div class="mt-4 flex justify-center gap-1.5">
      <button
        v-for="star in 5"
        :key="star"
        type="button"
        class="grid h-11 w-11 place-items-center rounded-xl text-2xl transition"
        :class="rating !== null && star <= rating ? 'text-brand' : 'text-line hover:text-brand/70'"
        :aria-label="$t('heardAbout.starLabel', { n: star })"
        @click="rating = star"
      >
        ★
      </button>
    </div>
    <p class="mt-1 text-center text-[11px] font-semibold text-ink-muted">{{ $t('heardAbout.rateHint') }}</p>

    <label class="mt-4 block text-xs font-semibold text-ink-muted" for="guest-feedback-comment">
      {{ $t('heardAbout.commentLabel') }}
    </label>
    <textarea
      id="guest-feedback-comment"
      v-model="comment"
      rows="3"
      maxlength="500"
      class="staff-input mt-1 resize-y"
      :placeholder="$t('heardAbout.commentPlaceholder')"
    />
    <p class="mt-1 text-right text-[11px] text-ink-muted">{{ comment.length }}/{{ COMMENT_MAX }}</p>

    <p class="mt-4 text-xs font-semibold text-ink-muted">{{ $t('heardAbout.guestQuestion') }}</p>
    <HeardAboutChips
      class="mt-2"
      :options="GUEST_HEARD_ABOUT"
      label-key="heardAbout.guest"
      :model-value="channel"
      @update:model-value="(code) => { channel = code as GuestHeardAboutChannel | null; }"
    />

    <div class="mt-5 flex justify-end gap-2">
      <BaseButton variant="secondary" :disabled="busy" @click="skip">{{ $t('heardAbout.skip') }}</BaseButton>
      <BaseButton :disabled="busy || !rating" @click="send">{{ $t('heardAbout.submitFeedback') }}</BaseButton>
    </div>
  </BaseModal>
</template>
