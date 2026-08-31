<script setup lang="ts">
import type { Announcement } from '~/types/announcement';

const api = useApi();
const announcements = ref<Announcement[]>([]);
const dismissing = ref<string | null>(null);

async function load() {
  try {
    announcements.value = await api.get<Announcement[]>('/staff/announcements');
  } catch {
    // Non-critical — skip silently, next mount/poll retries.
  }
}

async function dismiss(a: Announcement) {
  dismissing.value = a.id;
  try {
    await api.post(`/staff/announcements/${a.id}/dismiss`);
    announcements.value = announcements.value.filter((x) => x.id !== a.id);
  } catch {
    // Failure popup is shown by useApi.
  } finally {
    dismissing.value = null;
  }
}

const typeStyle: Record<string, string> = {
  INFO: 'bg-sky-50 border-sky-200 text-sky-900',
  WARNING: 'bg-amber-50 border-amber-200 text-amber-900',
  SUCCESS: 'bg-emerald-50 border-emerald-200 text-emerald-900',
  DANGER: 'bg-red-50 border-red-200 text-red-900',
  PROMOTION: 'bg-orange-50 border-brand/30 text-ink',
};

const liveRefresh = useLiveRefresh('/staff/events', load, ['announcement_updated']);
onMounted(() => {
  load();
  liveRefresh.start();
});
</script>

<template>
  <div v-if="announcements.length > 0" class="space-y-2 border-b border-[var(--line)] bg-[#f6f1ec] px-4 py-3 sm:px-6">
    <div
      v-for="a in announcements"
      :key="a.id"
      class="flex items-start justify-between gap-3 rounded-xl border px-3.5 py-2.5 text-sm shadow-sm"
      :class="typeStyle[a.type]"
    >
      <div class="min-w-0">
        <p class="font-display font-semibold">{{ a.title }}</p>
        <p class="mt-0.5 text-xs leading-relaxed opacity-90">{{ a.message }}</p>
      </div>
      <button
        v-if="a.isDismissible"
        type="button"
        class="shrink-0 rounded-lg px-2 py-1 text-xs font-semibold opacity-70 transition hover:bg-black/5 hover:opacity-100 disabled:opacity-40"
        :disabled="dismissing === a.id"
        @click="dismiss(a)"
      >
        {{ $t('common.dismiss') }}
      </button>
    </div>
  </div>
</template>
