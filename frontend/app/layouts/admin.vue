<script setup lang="ts">
const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const mobileOpen = ref(false);

const adminPath = useAdminPath();

const links = computed(() => [
  { to: adminPath.path('dashboard'), label: t('admin.nav.dashboard'), icon: 'grid' },
  { to: adminPath.path('restaurants'), label: t('admin.nav.restaurants'), icon: 'tables' },
  { to: adminPath.path('users'), label: t('admin.nav.users'), icon: 'team' },
  { to: adminPath.path('plans'), label: t('admin.nav.plans'), icon: 'cash' },
  { to: adminPath.path('support'), label: t('admin.nav.support'), icon: 'support' },
  { to: adminPath.path('announcements'), label: t('admin.nav.announcements'), icon: 'bell' },
  { to: adminPath.path('audit-log'), label: t('admin.nav.auditLog'), icon: 'log' },
  { to: adminPath.path('settings'), label: t('admin.nav.settings'), icon: 'settings' },
]);

const pageTitle = computed(() => {
  const match = links.value.find((l) => route.path === l.to || route.path.startsWith(`${l.to}/`));
  return match?.label ?? t('admin.nav.portal');
});

const prompt = useConfirm();
const toast = useToast();

async function logout() {
  const ok = await prompt.confirm({
    title: t('staff.nav.logoutTitle'),
    message: t('admin.nav.logoutConfirm'),
    confirmLabel: t('staff.nav.logout'),
    danger: true,
  });
  if (!ok) return;
  const api = useApi();
  try {
    await api.post('/auth/logout', undefined, { silent: true });
  } catch {
    // Local session is cleared either way so a failed network call cannot trap them.
  }
  auth.clearSession();
  toast.success(t('staff.nav.loggedOut'));
  await router.push('/staff/login');
}

function closeMobile() {
  mobileOpen.value = false;
}

watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false;
  },
);

const initials = computed(() => {
  const name = auth.staff.value?.fullName ?? '';
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length >= 2) return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
  return (parts[0]?.slice(0, 2) || 'SA').toUpperCase();
});
</script>

<template>
  <div class="flex min-h-screen bg-[#f2eddd] font-body text-ink">
    <div
      v-if="mobileOpen"
      class="fixed inset-0 z-40 bg-black/45 lg:hidden"
      @click="closeMobile"
    />

    <aside
      class="fixed inset-y-0 left-0 z-50 flex w-[222px] shrink-0 flex-col border-r-[6px] border-clay bg-admin-deep text-[#e6cdbe] transition-transform duration-200 lg:static lg:translate-x-0"
      :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <div class="flex items-center gap-2.5 px-5 pt-5">
        <BrandMark :size="30" tone="onDark" />
        <p class="truncate text-[15.5px] font-extrabold text-white">Smart Restaurant</p>
      </div>
      <p class="mb-5 mt-1.5 px-5 text-[10px] font-extrabold uppercase tracking-[0.14em] text-brand">
        {{ $t('admin.nav.portal') }}
      </p>

      <nav class="flex-1 overflow-y-auto px-3.5">
        <div class="space-y-0.5">
          <NuxtLink
            v-for="link in links"
            :key="link.to"
            :to="link.to"
            class="flex items-center gap-2.5 rounded-[10px] px-3 py-2.5 text-[13.5px] font-semibold text-[#c9a795] transition hover:bg-white/10 hover:text-white"
            active-class="!bg-white/13 !text-white"
          >
            <StaffNavIcon :name="link.icon" class="h-4 w-4 shrink-0 opacity-90" />
            {{ link.label }}
          </NuxtLink>
        </div>
      </nav>

      <div class="px-3.5 pb-4">
        <p class="truncate px-3 text-[11px] text-[#9a7a68]">Inovasiyo Ltd · Platform</p>
        <div class="mt-2 rounded-[10px] bg-white/10 px-3 py-2">
          <p class="truncate text-sm font-semibold text-white">{{ auth.staff.value?.fullName ?? $t('admin.layout.adminFallback') }}</p>
          <p class="truncate text-[11px] uppercase tracking-wide text-brand">{{ auth.staff.value?.role ?? $t('admin.layout.superadminFallback') }}</p>
        </div>
        <button
          type="button"
          class="mt-2 w-full rounded-[10px] px-3 py-2 text-left text-sm text-[#f6ded2] transition hover:bg-white/10"
          @click="logout"
        >
          {{ $t('staff.nav.logout') }}
        </button>
      </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col bg-diamonds-admin">
      <header class="sticky top-0 z-30 flex items-center justify-between gap-3 px-4 py-5 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
          <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-line bg-card text-ink lg:hidden"
            :aria-label="$t('staff.nav.openMenu')"
            @click="mobileOpen = true"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <div class="flex min-w-0 items-center gap-3">
            <h1 class="truncate font-display text-[21px] text-admin-deep">{{ pageTitle }}</h1>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#f6ded2] px-3 py-1 text-[11.5px] font-extrabold text-clay">
              {{ $t('admin.nav.subtitle') }}
            </span>
          </div>
        </div>
        <div class="flex items-center gap-2.5">
          <LanguageSwitcher tone="admin" />
          <span class="grid h-8 w-8 place-items-center rounded-full bg-clay text-xs font-extrabold text-white">
            {{ initials }}
          </span>
        </div>
      </header>

      <main class="flex-1">
        <slot />
      </main>
    </div>
  </div>
</template>
