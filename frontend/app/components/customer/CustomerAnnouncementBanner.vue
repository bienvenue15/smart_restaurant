<script setup lang="ts">
import type { Announcement } from '~/types/announcement';

const STORAGE_KEY = 'sr_dismissed_announcements';

const api = useApi();
const announcements = ref<Announcement[]>([]);

function readDismissed(): string[] {
  if (!import.meta.client) return [];
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? (JSON.parse(raw) as string[]) : [];
  } catch {
    return [];
  }
}

function persistDismissed(ids: string[]) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
}

async function load() {
  try {
    const all = await api.get<Announcement[]>('/customer/announcements');
    const dismissed = new Set(readDismissed());
    announcements.value = all.filter((a) => !dismissed.has(a.id));
  } catch {
    // Non-critical — skip silently.
  }
}

function dismiss(a: Announcement) {
  persistDismissed([...readDismissed(), a.id]);
  announcements.value = announcements.value.filter((x) => x.id !== a.id);
}

const typeStyle: Record<string, string> = {
  INFO: 'bg-sky-50 border-sky-200 text-sky-900',
  WARNING: 'bg-amber-50 border-amber-200 text-amber-900',
  SUCCESS: 'bg-emerald-50 border-emerald-200 text-emerald-900',
  DANGER: 'bg-red-50 border-red-200 text-red-900',
  PROMOTION: 'bg-orange-50 border-brand/30 text-ink',
};

const liveRefresh = useLiveRefresh('/customer/events', load, ['announcement_updated']);
onMounted(() => {
  load();
  liveRefresh.start();
});
</script>

<template>
  <div v-if="announcements.length > 0" class="mb-4 space-y-2">
    <div
      v-for="a in announcements"
      :key="a.id"
      class="flex items-start justify-between gap-3 rounded-xl border px-3.5 py-2.5 text-sm shadow-sm"
      :class="typeStyle[a.type]"
    >
      <div class="min-w-0">
        <p class="font-semibold">{{ a.title }}</p>
        <p class="mt-0.5 text-xs leading-relaxed opacity-90">{{ a.message }}</p>
      </div>
      <button
        v-if="a.isDismissible"
        type="button"
        class="shrink-0 rounded-lg px-2 py-1 text-xs font-semibold opacity-70 hover:bg-black/5 hover:opacity-100"
        @click="dismiss(a)"
      >
        {{ $t('common.dismiss') }}
      </button>
    </div>
  </div>
</template>
