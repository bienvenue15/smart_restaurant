<script setup lang="ts">
const notifications = useNotifications();
const callAlerts = useCallAlerts();
const { render: renderNotification } = useNotificationText();
const open = ref(false);

function toggle() {
  open.value = !open.value;
  if (open.value) void notifications.refresh();
}

async function handleMarkRead(id: string) {
  await notifications.markRead(id);
}

onMounted(() => {
  notifications.start();
  callAlerts.start();
});
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="relative inline-flex h-8 w-8 items-center justify-center rounded-full border border-line bg-card text-ink-muted transition hover:border-brand/40 hover:text-forest"
      :aria-label="$t('staff.nav.notifications')"
      @click="toggle"
    >
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"
        />
      </svg>
      <span
        v-if="notifications.unreadCount.value > 0"
        class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white"
      >
        {{ notifications.unreadCount.value > 9 ? '9+' : notifications.unreadCount.value }}
      </span>
    </button>

    <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />

    <div
      v-if="open"
      class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-line bg-card shadow-brand"
    >
      <div class="flex items-center justify-between border-b border-[var(--line)] bg-surface px-4 py-3">
        <span class="font-display text-sm font-bold text-ink">{{ $t('staff.nav.notifications') }}</span>
        <button
          v-if="notifications.unreadCount.value > 0"
          type="button"
          class="text-xs font-semibold text-brand hover:text-brand-dark"
          @click="notifications.markAllRead()"
        >
          {{ $t('staff.nav.markAllRead') }}
        </button>
      </div>

      <div class="max-h-96 overflow-y-auto">
        <p v-if="notifications.notifications.value.length === 0" class="p-6 text-center text-sm text-ink-muted">
          {{ $t('staff.nav.noNotifications') }}
        </p>

        <button
          v-for="n in notifications.notifications.value"
          :key="n.id"
          type="button"
          class="block w-full border-b border-gray-100 p-3.5 text-left last:border-0 hover:bg-surface"
          :class="{ 'bg-brand/5': !n.isRead }"
          @click="handleMarkRead(n.id)"
        >
          <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-semibold text-ink">{{ renderNotification(n).title }}</p>
            <span v-if="!n.isRead" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand" />
          </div>
          <p class="mt-0.5 text-xs leading-relaxed text-ink-muted">{{ renderNotification(n).message }}</p>
          <p class="mt-1.5 text-[11px] text-gray-400">{{ new Date(n.createdAt).toLocaleString() }}</p>
        </button>
      </div>
    </div>
  </div>
</template>
