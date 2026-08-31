<script setup lang="ts">
const { dialog, answer } = useConfirm();
const { t } = useI18n();
</script>

<template>
  <Teleport to="body">
    <div
      v-if="dialog.open"
      class="fixed inset-0 z-[70] flex items-end justify-center bg-black/45 p-4 sm:items-center"
      @click.self="answer(false)"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-title"
        class="w-full max-w-md rounded-2xl border border-line bg-card p-5 shadow-brand"
      >
        <p id="confirm-title" class="font-display text-lg font-bold text-ink">{{ dialog.title }}</p>
        <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ dialog.message }}</p>
        <div class="mt-5 flex justify-end gap-2">
          <BaseButton variant="secondary" @click="answer(false)">
            {{ dialog.cancelLabel || t('common.cancel') }}
          </BaseButton>
          <BaseButton :variant="dialog.danger ? 'danger' : 'primary'" @click="answer(true)">
            {{ dialog.confirmLabel || t('common.confirm') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </Teleport>
</template>
