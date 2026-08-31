<script setup lang="ts">
const visible = ref(false);

onMounted(() => {
  if (!import.meta.client) return;
  if (localStorage.getItem('sr_cookie_consent') !== '1') {
    visible.value = true;
  }
});

function accept() {
  localStorage.setItem('sr_cookie_consent', '1');
  visible.value = false;
}

function decline() {
  localStorage.setItem('sr_cookie_consent', '0');
  visible.value = false;
}
</script>

<template>
  <div
    v-if="visible"
    class="fixed inset-x-0 bottom-0 z-50 border-t border-line bg-card/95 p-4 shadow-brand backdrop-blur sm:p-5"
  >
    <div class="mx-auto flex max-w-6xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="font-display text-sm font-semibold text-ink">{{ $t('marketing.cookie.title') }}</p>
        <p class="mt-1 max-w-2xl text-sm text-ink-muted">
          {{ $t('marketing.cookie.body') }}
        </p>
      </div>
      <div class="flex shrink-0 gap-2">
        <button
          type="button"
          class="rounded-full border-[1.5px] border-forest px-4 py-2 text-sm font-bold text-forest hover:bg-forest hover:text-white"
          @click="decline"
        >
          {{ $t('marketing.cookie.decline') }}
        </button>
        <button
          type="button"
          class="rounded-full bg-brand px-4 py-2 text-sm font-bold text-[#3a2a05] hover:bg-brand-dark hover:text-white"
          @click="accept"
        >
          {{ $t('marketing.cookie.accept') }}
        </button>
      </div>
    </div>
  </div>
</template>
