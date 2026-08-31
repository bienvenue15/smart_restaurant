<script setup lang="ts">
import type { RestaurantTable } from '~/types/table';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['view_tables'] });

const { t } = useI18n();
const api = useApi();
const prompt = useConfirm();
const toast = useToast();
const auth = useAuthStore();
const { can } = usePermissions();
const canManageTables = computed(() => can('manage_tables'));
const canResetTable = computed(() => can('reset_table'));
const tables = ref<RestaurantTable[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creating = ref(false);
const newTableNumber = ref('');
const newSeats = ref(4);
const origin = ref('');
const previewTable = ref<RestaurantTable | null>(null);
const previewOpen = computed({
  get: () => previewTable.value !== null,
  set: (open: boolean) => {
    if (!open) previewTable.value = null;
  },
});

async function loadTables() {
  loading.value = true;
  error.value = null;
  try {
    tables.value = await api.get<RestaurantTable[]>('/staff/tables');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.tables.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function createTable() {
  if (!newTableNumber.value.trim()) return;
  creating.value = true;
  try {
    await api.post('/staff/tables', { tableNumber: newTableNumber.value, seats: newSeats.value });
    newTableNumber.value = '';
    newSeats.value = 4;
    await loadTables();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.tables.createFailed');
  } finally {
    creating.value = false;
  }
}

async function resetTable(table: RestaurantTable) {
  try {
    await api.post(`/staff/tables/${table.id}/reset`);
    await loadTables();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.tables.resetFailed');
  }
}

async function regenerateQr(table: RestaurantTable) {
  // Disruptive in a way delete/reset aren't — any QR code already printed
  // for this table stops working the instant this succeeds.
  const ok = await prompt.confirm({
    title: t('staff.tables.regenerateQr'),
    message: t('staff.tables.regenerateConfirm', { number: table.tableNumber }),
    confirmLabel: t('staff.tables.regenerateQr'),
    danger: true,
  });
  if (!ok) return;
  try {
    await api.post(`/staff/tables/${table.id}/regenerate-qr`);
    await loadTables();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.tables.regenerateFailed');
  }
}

async function removeTable(table: RestaurantTable) {
  try {
    await api.del(`/staff/tables/${table.id}`);
    await loadTables();
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.tables.deleteFailed');
  }
}

function menuUrl(table: RestaurantTable): string {
  if (!origin.value) return '';
  return `${origin.value}/menu/${table.qrCode}`;
}

async function copyLink(table: RestaurantTable) {
  try {
    await navigator.clipboard.writeText(menuUrl(table));
    toast.success(t('common.copied'));
  } catch {
    toast.error(t('common.actionFailed'), t('common.copyFailed'));
  }
}

async function qrPng(table: RestaurantTable, size: number): Promise<string> {
  const QRCode = (await import('qrcode')).default;
  return QRCode.toDataURL(menuUrl(table), {
    width: size,
    margin: 2,
    errorCorrectionLevel: 'M',
    color: { dark: '#221a10', light: '#ffffff' },
  });
}

async function printQr(table: RestaurantTable) {
  const dataUrl = await qrPng(table, 420);
  const restaurant = auth.staff.value?.restaurant?.name ?? '';
  const title = t('staff.tables.tableLabel', { number: table.tableNumber });
  const w = window.open('', '_blank');
  if (!w) return;
  w.document.write(`<!doctype html><html><head><title>${escapeHtml(title)}</title>
<style>
  body { font-family: system-ui, sans-serif; text-align: center; padding: 32px; color: #221a10; }
  h1 { font-size: 28px; margin: 0 0 8px; }
  p { margin: 0 0 16px; color: #5d5343; }
  img { width: 280px; height: 280px; }
</style></head><body>
  ${restaurant ? `<p>${escapeHtml(restaurant)}</p>` : ''}
  <h1>${escapeHtml(title)}</h1>
  <p>${escapeHtml(t('staff.tables.scanHint'))}</p>
  <img src="${dataUrl}" alt="${escapeHtml(t('staff.tables.qrAlt', { number: table.tableNumber }))}" />
</body></html>`);
  w.document.close();
  w.focus();
  w.print();
}

async function printAllQrs() {
  if (tables.value.length === 0) return;
  const restaurant = auth.staff.value?.restaurant?.name ?? '';
  const cards = await Promise.all(
    tables.value.map(async (table) => {
      const dataUrl = await qrPng(table, 360);
      const title = t('staff.tables.tableLabel', { number: table.tableNumber });
      return `<article>
        ${restaurant ? `<p class="brand">${escapeHtml(restaurant)}</p>` : ''}
        <h1>${escapeHtml(title)}</h1>
        <p>${escapeHtml(t('staff.tables.scanHint'))}</p>
        <img src="${dataUrl}" alt="${escapeHtml(t('staff.tables.qrAlt', { number: table.tableNumber }))}" />
      </article>`;
    }),
  );
  const w = window.open('', '_blank');
  if (!w) return;
  w.document.write(`<!doctype html><html><head><title>${escapeHtml(t('staff.tables.printAll'))}</title>
<style>
  body { font-family: system-ui, sans-serif; color: #221a10; margin: 0; }
  section { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding: 24px; }
  article { break-inside: avoid; text-align: center; border: 1px solid #e3d8bf; border-radius: 12px; padding: 20px; }
  h1 { font-size: 22px; margin: 0 0 6px; }
  p { margin: 0 0 12px; color: #5d5343; font-size: 13px; }
  .brand { font-weight: 700; color: #221a10; }
  img { width: 220px; height: 220px; }
  @media print { article { border-color: #ccc; } }
</style></head><body><section>${cards.join('')}</section></body></html>`);
  w.document.close();
  w.focus();
  w.print();
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

const statusColor: Record<string, string> = {
  AVAILABLE: 'bg-emerald-100 text-emerald-800',
  OCCUPIED: 'bg-amber-100 text-amber-900',
  RESERVED: 'bg-sky-100 text-sky-800',
  CLEANING: 'bg-gray-100 text-gray-700',
};

function tableStatusLabel(status: string): string {
  return t(`staff.tables.status.${status.toLowerCase()}`);
}

// order_status is included because a new order silently flips a table
// AVAILABLE -> OCCUPIED as a side effect (order.service.ts::createOrder).
const liveRefresh = useLiveRefresh('/staff/events', loadTables, ['table_status', 'order_status']);
onMounted(() => {
  origin.value = window.location.origin;
  loadTables();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.tables')" :subtitle="$t('staff.pages.tablesSub')">
      <template #actions>
        <BaseButton v-if="canManageTables" variant="secondary" :disabled="loading || tables.length === 0" @click="printAllQrs">
          {{ $t('staff.tables.printAll') }}
        </BaseButton>
        <BaseButton variant="secondary" :disabled="loading" @click="loadTables">{{ $t('common.refresh') }}</BaseButton>
      </template>
    </StaffPageHeader>

    <form v-if="canManageTables" class="staff-card mb-5 flex flex-wrap items-end gap-3" @submit.prevent="createTable">
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.tables.tableNumberLabel') }}</label>
        <input v-model="newTableNumber" class="staff-input" placeholder="T1" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-ink-muted">{{ $t('staff.tables.seatsLabel') }}</label>
        <input v-model.number="newSeats" type="number" min="1" class="staff-input w-20" />
      </div>
      <BaseButton type="submit" :disabled="creating">{{ $t('staff.tables.addTable') }}</BaseButton>
    </form>

    <p v-if="error" class="staff-error">{{ error }}</p>
    <div v-if="loading" class="staff-empty">{{ $t('staff.tables.loading') }}</div>
    <div v-else-if="tables.length === 0" class="staff-empty">{{ $t('staff.tables.noTables') }}</div>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <article v-for="table in tables" :key="table.id" class="staff-card">
        <div class="mb-2 flex items-center justify-between gap-2">
          <p class="font-display font-bold text-ink">{{ $t('staff.tables.tableLabel', { number: table.tableNumber }) }}</p>
          <span class="staff-chip" :class="statusColor[table.status]">{{ tableStatusLabel(table.status) }}</span>
        </div>
        <p class="text-sm text-ink-muted">{{ $t('staff.tables.seatsCount', { count: table.seats }) }}</p>

        <button
          type="button"
          class="mx-auto mt-4 block w-[11.5rem] rounded-xl border border-[var(--line)] bg-white p-2 transition hover:border-brand/50"
          :aria-label="$t('staff.tables.enlargeQr', { number: table.tableNumber })"
          @click="previewTable = table"
        >
          <QrCodeImage :value="menuUrl(table)" :size="176" :alt="$t('staff.tables.qrAlt', { number: table.tableNumber })" />
        </button>
        <p class="mt-2 text-center text-xs text-ink-muted">{{ $t('staff.tables.scanHint') }}</p>

        <div class="mt-3 flex flex-wrap gap-2">
          <BaseButton variant="secondary" @click="printQr(table)">{{ $t('staff.tables.printQr') }}</BaseButton>
          <BaseButton variant="secondary" @click="copyLink(table)">{{ $t('staff.tables.copyLink') }}</BaseButton>
          <BaseButton v-if="canResetTable" variant="secondary" @click="resetTable(table)">{{ $t('staff.tables.reset') }}</BaseButton>
          <BaseButton v-if="canManageTables" variant="secondary" @click="regenerateQr(table)">{{ $t('staff.tables.regenerateQr') }}</BaseButton>
          <BaseButton v-if="canManageTables" variant="danger" @click="removeTable(table)">{{ $t('common.delete') }}</BaseButton>
        </div>
      </article>
    </div>

    <BaseModal v-model="previewOpen">
      <template v-if="previewTable">
        <p class="font-display text-lg font-bold text-ink">
          {{ $t('staff.tables.tableLabel', { number: previewTable.tableNumber }) }}
        </p>
        <p class="mt-1 text-sm text-ink-muted">{{ $t('staff.tables.scanHint') }}</p>
        <div class="mx-auto mt-4 w-64 rounded-xl border border-[var(--line)] bg-white p-3">
          <QrCodeImage
            :value="menuUrl(previewTable)"
            :size="256"
            :alt="$t('staff.tables.qrAlt', { number: previewTable.tableNumber })"
          />
        </div>
        <div class="mt-4 flex flex-wrap justify-end gap-2">
          <BaseButton variant="secondary" @click="printQr(previewTable)">{{ $t('staff.tables.printQr') }}</BaseButton>
          <BaseButton variant="secondary" @click="previewTable = null">{{ $t('common.close') }}</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
