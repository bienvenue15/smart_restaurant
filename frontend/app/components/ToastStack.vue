<script setup lang="ts">
const { toasts, dismiss } = useToast();

const kindClass: Record<string, string> = {
  success: 'border-emerald/40 bg-white',
  error: 'border-clay/50 bg-white',
  info: 'border-[var(--line)] bg-white',
};

const accentClass: Record<string, string> = {
  success: 'bg-emerald',
  error: 'bg-clay',
  info: 'bg-brand',
};
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:right-4 sm:left-auto">
    <TransitionGroup name="toast">
      <div
        v-for="item in toasts"
        :key="item.id"
        class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-2xl border p-0 shadow-xl"
        :class="kindClass[item.kind]"
      >
        <div class="flex">
          <span class="w-1.5 shrink-0" :class="accentClass[item.kind]" />
          <div class="min-w-0 flex-1 p-3.5">
            <div class="flex items-start justify-between gap-2">
              <p class="text-sm font-bold text-ink">{{ item.title }}</p>
              <button
                type="button"
                class="shrink-0 text-ink-muted hover:text-ink"
                :aria-label="$t('common.dismiss')"
                @click="dismiss(item.id)"
              >
                &times;
              </button>
            </div>
            <p v-if="item.message" class="mt-1 text-xs leading-relaxed text-ink-muted">{{ item.message }}</p>
          </div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(16px);
}
</style>
