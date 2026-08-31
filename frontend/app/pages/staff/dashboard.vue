<script setup lang="ts">
import { PLATFORM_HEARD_ABOUT, type PlatformHeardAbout } from '~/utils/heardAbout';
import type { ShiftStatus } from '~/types/staff';

definePageMeta({ middleware: 'staff-auth', layout: 'staff' });

const { t } = useI18n();
const api = useApi();
const auth = useAuthStore();

// Shared with the sidebar nav badges (layouts/staff.vue), which owns the
// single start()/poll loop for this data (the layout is always mounted
// alongside this page) — read-only here, not a second independent fetch loop.
const { stats } = useStaffStats();
const shiftStatus = ref<ShiftStatus | null>(null);
const loading = ref(true);
const shiftBusy = ref(false);
const heardBusy = ref(false);
const heardHidden = ref(false);
const error = ref<string | null>(null);

const showHeardAbout = computed(
  () => auth.staff.value?.role === 'ADMIN' && stats.value?.askHowYouFoundUs && !heardHidden.value,
);

async function saveHeardAbout(channel: PlatformHeardAbout | null, skipped: boolean) {
  heardBusy.value = true;
  try {
    await api.patch('/staff/restaurants/me', skipped ? { heardAboutSkipped: true } : { heardAboutUs: channel });
    heardHidden.value = true;
    if (stats.value) stats.value.askHowYouFoundUs = false;
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('common.error');
  } finally {
    heardBusy.value = false;
  }
}

async function loadDashboard() {
  loading.value = true;
  error.value = null;
  try {
    shiftStatus.value = await api.get<ShiftStatus>('/staff/users/me/shift-status');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.dashboard.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function toggleShift() {
  shiftBusy.value = true;
  try {
    if (shiftStatus.value?.onShift) {
      await api.post('/staff/users/me/clock-out');
    } else {
      await api.post('/staff/users/me/clock-in');
    }
    shiftStatus.value = await api.get<ShiftStatus>('/staff/users/me/shift-status');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.dashboard.shiftUpdateFailed');
  } finally {
    shiftBusy.value = false;
  }
}

const { can, canAny, canHandleCashDrawer } = usePermissions();
const { hasFeature } = usePlan();

const canManageApprovals = computed(() => canAny('approve_actions', 'manage_staff'));
const canSeeTables = computed(() => can('view_tables'));
const canSeeWaiterCalls = computed(() => can('handle_waiter_calls'));
const canSeeKitchen = computed(() => kitchenDisplayAllowed(auth.staff.value));
const canViewReports = computed(() => can('view_reports') && hasFeature('analytics'));
const canSeeOrders = computed(() => can('manage_orders') || can('accept_payment'));
const canOpenCash = computed(() => canHandleCashDrawer());

const statTiles = computed(() => [
  ...(canSeeOrders.value
    ? [
        { label: t('staff.dashboard.todayOrders'), value: stats.value?.todayOrders ?? '—', hint: t('staff.dashboard.hintPlacedToday'), to: '/staff/orders' },
        {
          label: t('staff.dashboard.pendingOrders'),
          value: stats.value?.pendingOrders ?? '—',
          hint: t('staff.dashboard.hintNeedsAction'),
          to: { path: '/staff/orders', query: { status: 'PENDING' } },
        },
      ]
    : []),
  ...(canViewReports.value || canOpenCash.value
    ? [
        {
          label: t('staff.dashboard.todayRevenue'),
          value: stats.value ? Number(stats.value.todayRevenue).toLocaleString() : '—',
          hint: 'RWF',
          to: canViewReports.value ? '/staff/reports' : undefined,
        },
      ]
    : []),
  {
    label: t('staff.dashboard.activeTables'),
    value: stats.value?.activeTables ?? '—',
    hint: t('staff.dashboard.hintOccupiedNow'),
    to: canSeeTables.value ? '/staff/tables' : undefined,
  },
  ...(canSeeWaiterCalls.value
    ? [
        {
          label: t('staff.nav.waiterCalls'),
          value: stats.value?.pendingWaiterCalls ?? '—',
          hint: t('staff.dashboard.hintOpenRequests'),
          to: '/staff/waiter-calls',
        },
      ]
    : []),
  ...(canManageApprovals.value
    ? [
        {
          label: t('staff.nav.approvals'),
          value: stats.value?.pendingApprovals ?? '—',
          hint: t('staff.dashboard.hintQueued'),
          to: '/staff/approvals',
        },
      ]
    : []),
  ...(canSeeOrders.value || canManageApprovals.value
    ? [
        {
          label: t('staff.nav.liabilities'),
          value: stats.value?.pendingLiabilities ?? '—',
          hint: t('staff.dashboard.hintActiveLiability'),
          to: '/staff/liabilities',
        },
      ]
    : []),
]);

const quickLinks = computed(() => {
  const links = [];
  if (canSeeOrders.value) {
    links.push({ to: '/staff/orders', label: t('staff.nav.orders'), desc: t('staff.dashboard.descOrders') });
  }
  if (canSeeKitchen.value) {
    links.push({ to: '/staff/kitchen', label: t('staff.nav.kitchen'), desc: t('staff.dashboard.descKitchen') });
  }
  if (canSeeWaiterCalls.value) {
    links.push({ to: '/staff/waiter-calls', label: t('staff.dashboard.callsLabel'), desc: t('staff.dashboard.descCalls') });
  }
  if (canSeeTables.value) {
    links.push({ to: '/staff/tables', label: t('staff.nav.tables'), desc: t('staff.dashboard.descTables') });
  }
  if (canManageApprovals.value) {
    links.push({ to: '/staff/approvals', label: t('staff.nav.approvals'), desc: t('staff.dashboard.descApprovals') });
    links.push({ to: '/staff/activity-log', label: t('staff.dashboard.activityLabel'), desc: t('staff.dashboard.descActivity') });
  }
  return links;
});

onMounted(loadDashboard);
</script>

<template>
  <div>
    <StaffPageHeader
      :title="$t('staff.pages.welcome', { name: auth.staff.value?.fullName ?? $t('staff.dashboard.fallbackName') })"
      :subtitle="$t('staff.pages.welcomeSub', { role: auth.staff.value?.role ?? $t('staff.dashboard.fallbackRole') })"
    >
      <template #actions>
        <span
          class="staff-chip"
          :class="shiftStatus?.onShift ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'"
        >
          {{ shiftStatus?.onShift ? $t('staff.dashboard.onShift') : $t('staff.dashboard.offShift') }}
        </span>
        <BaseButton
          :variant="shiftStatus?.onShift ? 'danger' : 'primary'"
          :disabled="shiftBusy || loading"
          @click="toggleShift"
        >
          {{ shiftStatus?.onShift ? $t('staff.dashboard.clockOut') : $t('staff.dashboard.clockIn') }}
        </BaseButton>
      </template>
    </StaffPageHeader>

    <p v-if="error" class="staff-error">{{ error }}</p>

    <div v-if="loading" class="staff-empty">{{ $t('staff.dashboard.loading') }}</div>

    <template v-else>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:gap-4">
        <component
          :is="tile.to ? 'NuxtLink' : 'div'"
          v-for="(tile, index) in statTiles"
          :key="tile.label"
          :to="tile.to"
          class="staff-card relative overflow-hidden"
          :class="tile.to ? 'transition hover:-translate-y-0.5 hover:border-brand' : ''"
        >
          <div
            class="pointer-events-none absolute -right-4 -top-4 h-16 w-16 rounded-full opacity-20"
            :class="index % 2 === 0 ? 'bg-brand' : 'bg-accent'"
          />
          <p class="text-xs font-bold text-ink-muted">{{ tile.label }}</p>
          <p class="mt-1 font-display text-[22px] font-bold tracking-tight text-forest">
            {{ tile.value }}
          </p>
          <p class="mt-1 text-xs text-ink-muted">{{ tile.hint }}</p>
        </component>
      </div>

      <div
        v-if="showHeardAbout"
        class="mt-5 rounded-2xl border border-brand/30 bg-card px-4 py-4"
      >
        <p class="text-sm font-semibold text-ink">{{ $t('heardAbout.staffQuestion') }}</p>
        <p class="mt-1 text-xs text-ink-muted">{{ $t('heardAbout.staffHint') }}</p>
        <HeardAboutChips
          class="mt-3"
          :options="PLATFORM_HEARD_ABOUT"
          label-key="heardAbout.platform"
          :model-value="null"
          @update:model-value="(code) => { if (code) void saveHeardAbout(code as PlatformHeardAbout, false); }"
        />
        <button type="button" class="mt-3 text-xs font-semibold text-ink-muted" :disabled="heardBusy" @click="saveHeardAbout(null, true)">
          {{ $t('heardAbout.skip') }}
        </button>
      </div>

      <div
        v-if="!shiftStatus?.onShift"
        class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
      >
        {{ $t('staff.dashboard.notClockedIn') }}
      </div>

      <div class="mt-6">
        <h2 class="mb-3 font-display text-sm font-bold uppercase tracking-wide text-ink-muted">{{ $t('staff.dashboard.quickLinks') }}</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <NuxtLink
            v-for="link in quickLinks"
            :key="link.to"
            :to="link.to"
            class="staff-card transition hover:-translate-y-0.5 hover:border-brand"
          >
            <p class="font-display font-bold text-ink">{{ link.label }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ link.desc }}</p>
          </NuxtLink>
        </div>
      </div>
    </template>
  </div>
</template>
