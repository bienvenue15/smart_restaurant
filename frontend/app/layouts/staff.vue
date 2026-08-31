<script setup lang="ts">
const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const { t } = useI18n();
const runtimeConfig = useRuntimeConfig();

const restaurantBrand = computed(() => auth.staff.value?.restaurant ?? null);
const restaurantName = computed(() => restaurantBrand.value?.name || t('staff.nav.portal'));
const restaurantLogo = computed(() =>
  resolveMediaUrl(restaurantBrand.value?.logoUrl, runtimeConfig.public.apiBaseUrl as string),
);
const restaurantInitial = computed(() => restaurantName.value.trim().charAt(0).toUpperCase() || 'R');

const mobileOpen = ref(false);
const { can, canAny, canHandleCashDrawer } = usePermissions();
const { hasFeature, subscriptionActive } = usePlan();
const authRole = computed(() => auth.staff.value?.role);

const canManageApprovals = computed(() => canAny('approve_actions', 'manage_staff'));
const canSeeKitchen = computed(() => kitchenDisplayAllowed(auth.staff.value));
const canSeeTables = computed(() => can('view_tables'));
const canSeeWaiterCalls = computed(() => can('handle_waiter_calls'));
const canSeeOrders = computed(() => can('manage_orders') || can('accept_payment'));
const canManageTeam = computed(() => can('manage_staff'));
const canOpenCash = computed(() => canHandleCashDrawer());
const canViewReports = computed(() => can('view_reports') && hasFeature('analytics'));
const canViewFinancials = computed(() => can('view_financials') && hasFeature('analytics'));
const canManageSettings = computed(() => can('manage_settings'));
const canSeeLiabilities = computed(() => canSeeOrders.value || canManageApprovals.value);
const canSeeMenu = computed(() => can('view_menu'));

const { stats: navStats, start: startNavStats } = useStaffStats();
const BADGE_COUNTS: Record<string, () => number | undefined> = {
  '/staff/orders': () => navStats.value?.pendingOrders,
  '/staff/waiter-calls': () => navStats.value?.pendingWaiterCalls,
  '/staff/liabilities': () => navStats.value?.pendingLiabilities,
  '/staff/approvals': () => navStats.value?.pendingApprovals,
};
function badgeCount(to: string): number | null {
  const count = BADGE_COUNTS[to]?.();
  return count && count > 0 ? count : null;
}

const navGroups = computed(() => {
  const kitchenLink = canSeeKitchen.value
    ? [{ to: '/staff/kitchen', label: t('staff.nav.kitchen'), icon: 'kitchen' }]
    : [];
  const dashboardLink = [{ to: '/staff/dashboard', label: t('staff.nav.dashboard'), icon: 'grid' }];
  const ordersLink = canSeeOrders.value
    ? [{ to: '/staff/orders', label: t('staff.nav.orders'), icon: 'orders' }]
    : [];
  const afterKitchen = [
    ...(canSeeWaiterCalls.value ? [{ to: '/staff/waiter-calls', label: t('staff.nav.waiterCalls'), icon: 'bell' }] : []),
    ...(canSeeTables.value ? [{ to: '/staff/tables', label: t('staff.nav.tables'), icon: 'tables' }] : []),
  ];
  const floorLinks =
    authRole.value === 'KITCHEN'
      ? [...kitchenLink, ...dashboardLink, ...ordersLink, ...afterKitchen]
      : [...dashboardLink, ...ordersLink, ...kitchenLink, ...afterKitchen];

  return [
  {
    label: t('staff.nav.floor'),
    links: floorLinks,
  },
  {
    label: t('staff.nav.manage'),
    links: [
      ...(canSeeMenu.value ? [{ to: '/staff/menu', label: t('staff.nav.menu'), icon: 'menu' }] : []),
      ...(canManageTeam.value ? [{ to: '/staff/team', label: t('staff.nav.team'), icon: 'team' }] : []),
      ...(canOpenCash.value ? [{ to: '/staff/cash', label: t('staff.nav.cash'), icon: 'cash' }] : []),
      ...(canViewReports.value ? [{ to: '/staff/reports', label: t('staff.nav.reports'), icon: 'reports' }] : []),
      ...(canViewFinancials.value ? [{ to: '/staff/financials', label: t('staff.nav.financials'), icon: 'reports' }] : []),
      ...(canSeeLiabilities.value ? [{ to: '/staff/liabilities', label: t('staff.nav.liabilities'), icon: 'liability' }] : []),
      ...(canManageApprovals.value
        ? [
            { to: '/staff/approvals', label: t('staff.nav.approvals'), icon: 'approvals' },
            { to: '/staff/activity-log', label: t('staff.nav.activityLog'), icon: 'log' },
          ]
        : []),
    ],
  },
  {
    label: t('staff.nav.accountGroup'),
    links: [
      { to: '/staff/support', label: t('staff.nav.support'), icon: 'support' },
      { to: '/staff/account', label: t('staff.nav.account'), icon: 'account' },
      ...(canManageSettings.value ? [{ to: '/staff/settings', label: t('staff.nav.settings'), icon: 'settings' }] : []),
    ],
  },
  ];
});

const pageTitle = computed(() => {
  for (const group of navGroups.value) {
    const match = group.links.find((l) => route.path === l.to || route.path.startsWith(`${l.to}/`));
    if (match) return match.label;
  }
  return t('staff.nav.portal');
});

const prompt = useConfirm();
const toast = useToast();

async function logout() {
  const ok = await prompt.confirm({
    title: t('staff.nav.logoutTitle'),
    message: t('staff.nav.logoutConfirm'),
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

const DELAY_CHECK_INTERVAL_MS = 30000;
let delayCheckTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  const api = useApi();
  const runCheck = () => {
    void api.post('/staff/orders/check-delays', undefined, { silent: true }).catch(() => {});
  };
  runCheck();
  delayCheckTimer = setInterval(runCheck, DELAY_CHECK_INTERVAL_MS);
  startNavStats();
});

onScopeDispose(() => {
  if (delayCheckTimer) clearInterval(delayCheckTimer);
});

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
  return (parts[0]?.slice(0, 2) || 'ST').toUpperCase();
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
      class="fixed inset-y-0 left-0 z-50 flex w-[216px] shrink-0 flex-col border-r-[6px] border-brand bg-forest text-[#cfe3d8] transition-transform duration-200 lg:static lg:translate-x-0"
      :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <div class="flex items-center gap-2.5 px-5 pb-1 pt-5">
        <img
          v-if="restaurantLogo"
          :src="restaurantLogo"
          alt=""
          class="h-[30px] w-[30px] rounded-lg bg-white/15 object-contain p-0.5"
        />
        <BrandMark v-else :size="30" tone="onDark" />
        <div class="min-w-0 leading-tight">
          <p class="truncate text-base font-extrabold text-white">Smart Restaurant</p>
        </div>
      </div>

      <nav class="flex-1 overflow-y-auto px-3.5 py-4">
        <div v-for="group in navGroups" :key="group.label" class="mb-4">
          <p class="mb-1 px-3 text-[10px] font-extrabold uppercase tracking-[0.14em] text-[#7fa392]">
            {{ group.label }}
          </p>
          <div class="space-y-0.5">
            <NuxtLink
              v-for="link in group.links"
              :key="link.to"
              :to="link.to"
              class="flex items-center gap-2.5 rounded-[10px] px-3 py-2.5 text-[13.5px] font-semibold text-[#a9c6b8] transition hover:bg-white/10 hover:text-white"
              active-class="!bg-white/13 !text-white"
            >
              <StaffNavIcon :name="link.icon" class="h-4 w-4 shrink-0 opacity-90" />
              <span class="flex-1">{{ link.label }}</span>
              <span
                v-if="badgeCount(link.to)"
                class="ml-auto inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-clay px-1.5 text-[11px] font-bold text-white"
              >
                {{ badgeCount(link.to) }}
              </span>
            </NuxtLink>
          </div>
        </div>
      </nav>

      <div class="px-3.5 pb-4">
        <p class="truncate px-3 text-[11px] text-[#7fa392]">{{ restaurantName }}</p>
        <div class="mt-2 rounded-[10px] bg-white/10 px-3 py-2">
          <p class="truncate text-sm font-semibold text-white">{{ auth.staff.value?.fullName }}</p>
          <p class="truncate text-[11px] uppercase tracking-wide text-brand">
            {{ auth.staff.value?.role ? $t(`staff.team.role.${auth.staff.value.role.toLowerCase()}`) : '' }}
          </p>
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

    <div class="flex min-w-0 flex-1 flex-col bg-diamonds">
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
            <h1 class="truncate font-display text-[21px] text-forest">{{ pageTitle }}</h1>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#e8f4ee] px-3 py-1 text-[11.5px] font-extrabold text-emerald">
              <span class="h-2 w-2 rounded-full bg-emerald" />
              LIVE
            </span>
          </div>
        </div>
        <div class="flex items-center gap-2.5">
          <LanguageSwitcher />
          <NotificationBell />
          <span class="grid h-8 w-8 place-items-center rounded-full bg-brand text-xs font-extrabold text-white">
            {{ initials }}
          </span>
        </div>
      </header>

      <AnnouncementBanner />

      <p
        v-if="!subscriptionActive"
        class="mx-4 mb-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 sm:mx-6"
      >
        {{ $t('staff.settings.subscriptionExpired') }}
      </p>

      <main class="flex-1 px-4 pb-6 sm:px-6">
        <slot />
      </main>
    </div>
  </div>
</template>
