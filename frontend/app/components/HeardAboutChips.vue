<script setup lang="ts">
const props = defineProps<{
  options: readonly string[];
  labelKey: string;
  modelValue: string | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string | null];
}>();
</script>

<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="code in props.options"
      :key="code"
      type="button"
      class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
      :class="
        props.modelValue === code
          ? 'border-brand bg-brand text-white'
          : 'border-gray-200 bg-white text-ink hover:border-brand/40'
      "
      @click="emit('update:modelValue', props.modelValue === code ? null : code)"
    >
      {{ $t(`${props.labelKey}.${code}`) }}
    </button>
  </div>
</template>
