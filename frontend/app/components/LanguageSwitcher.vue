<script setup lang="ts">
withDefaults(
  defineProps<{
    tone?: 'sand' | 'onDark' | 'admin';
  }>(),
  { tone: 'sand' },
);

const { locale, locales, setLocale } = useI18n();

const options = computed(() =>
  (locales.value as Array<{ code: string; name: string }>).map((item) => ({
    code: item.code,
    name: item.name,
  })),
);
</script>

<template>
  <div
    class="inline-flex items-center gap-0.5 rounded-full p-[3px] text-[11px] font-extrabold"
    :class="
      tone === 'onDark'
        ? 'bg-white/12'
        : 'border border-line bg-sand'
    "
  >
    <button
      v-for="item in options"
      :key="item.code"
      type="button"
      class="rounded-full px-2.5 py-1 uppercase tracking-wide transition"
      :class="
        locale === item.code
          ? tone === 'admin'
            ? 'bg-clay text-white'
            : tone === 'onDark'
              ? 'bg-white text-forest'
              : 'bg-forest text-white'
          : tone === 'onDark'
            ? 'text-[#bcd6ca] hover:text-white'
            : 'text-ink-muted hover:text-ink'
      "
      :aria-pressed="locale === item.code"
      :title="item.name"
      @click="setLocale(item.code as 'en' | 'fr' | 'rw' | 'sw')"
    >
      {{ item.code }}
    </button>
  </div>
</template>
