<script setup lang="ts">
const retrying = ref(false);

async function retry() {
  if (retrying.value) return;
  retrying.value = true;
  // Full reload re-runs the page's first API call. If maintenance is still
  // on, useApi will raise the overlay again; if it's off, they land on a live page.
  window.location.reload();
}
</script>

<template>
  <div class="fixed inset-0 z-[90] flex flex-col bg-forest bg-diamonds text-white">
    <div class="flex items-center justify-between px-4 py-4 sm:px-6">
      <div class="flex items-center gap-2.5">
        <BrandMark :size="34" tone="onDark" />
        <span class="text-sm font-bold">Smart Restaurant</span>
      </div>
      <LanguageSwitcher tone="onDark" />
    </div>

    <div class="flex flex-1 flex-col items-center justify-center px-6 pb-16 text-center">
      <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand">{{ $t('maintenancePage.kicker') }}</p>
      <h1 class="mt-3 max-w-lg font-display text-3xl font-bold sm:text-4xl">{{ $t('maintenancePage.title') }}</h1>
      <p class="mt-4 max-w-md text-sm leading-relaxed text-[#c5d9cf] sm:text-base">
        {{ $t('maintenancePage.body') }}
      </p>
      <p class="mt-3 max-w-md text-sm leading-relaxed text-[#9cb5aa]">
        {{ $t('maintenancePage.detail') }}
      </p>
      <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <BaseButton :disabled="retrying" @click="retry">
          {{ retrying ? $t('common.loading') : $t('maintenancePage.retry') }}
        </BaseButton>
        <NuxtLink
          to="/staff/login"
          class="rounded-full border-[1.5px] border-white/30 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-white/10"
        >
          {{ $t('maintenancePage.signIn') }}
        </NuxtLink>
      </div>
    </div>
  </div>
</template>
