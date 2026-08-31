<script setup lang="ts">
import type { CashSession, CashTransaction } from '~/types/cash';

definePageMeta({ middleware: 'staff-auth', layout: 'staff', permissions: ['handle_cash'] });

const EXPENSE_CATEGORIES = [
  'electricity',
  'water',
  'gas',
  'internet',
  'supplies',
  'maintenance',
  'transport',
  'other',
] as const;

type LedgerKind = 'EXPENSE' | 'WITHDRAWAL' | 'DEPOSIT';

const { t } = useI18n();
const api = useApi();
const { can } = usePermissions();
const session = ref<CashSession | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const openingBalance = ref<number | null>(null);
const closingBalance = ref<number | null>(null);
const busy = ref(false);

const payoutKind = ref<LedgerKind>('EXPENSE');
const category = ref<(typeof EXPENSE_CATEGORIES)[number]>('electricity');
const amount = ref<number | null>(null);
const description = ref('');
const referenceNumber = ref('');
const recording = ref(false);

const cashInHand = computed(() => (session.value ? Number(session.value.cashInHand) : 0));
const awaitingClose = computed(() => session.value?.status === 'AUDITING');
const canFinalizeClose = computed(() => can('approve_actions'));
const countedCash = computed(() => (session.value?.closingBalance != null ? Number(session.value.closingBalance) : null));
const expectedCash = computed(() =>
  session.value?.expectedBalance != null ? Number(session.value.expectedBalance) : cashInHand.value,
);

const kindHint = computed(() => {
  if (payoutKind.value === 'WITHDRAWAL') return t('staff.cash.kindWithdrawalHint');
  if (payoutKind.value === 'DEPOSIT') return t('staff.cash.kindDepositHint');
  return t('staff.cash.kindExpenseHint');
});

const reasonPlaceholder = computed(() => {
  if (payoutKind.value === 'WITHDRAWAL') return t('staff.cash.reasonPlaceholderWithdrawal');
  if (payoutKind.value === 'DEPOSIT') return t('staff.cash.reasonPlaceholderDeposit');
  return t('staff.cash.reasonPlaceholderExpense');
});

const recordLabel = computed(() => {
  if (payoutKind.value === 'WITHDRAWAL') return t('staff.cash.recordWithdrawal');
  if (payoutKind.value === 'DEPOSIT') return t('staff.cash.recordDeposit');
  return t('staff.cash.recordExpense');
});

function typeLabel(type: string): string {
  const key = `staff.cash.types.${type}`;
  const label = t(key);
  return label === key ? type : label;
}

function categoryLabel(code: string | null): string {
  if (!code) return '';
  const key = `staff.cash.categories.${code}`;
  const label = t(key);
  return label === key ? code : label;
}

function parsedCategory(tx: CashTransaction): string | null {
  if (tx.category) return tx.category;
  const match = tx.description?.match(/^\[([a-z]+)\]/);
  return match?.[1] ?? null;
}

function displayDescription(tx: CashTransaction): string {
  return (tx.description || '').replace(/^\[[a-z]+\]\s*/, '');
}

function txTitle(tx: CashTransaction): string {
  const code = parsedCategory(tx);
  if (tx.transactionType === 'EXPENSE' && code) return categoryLabel(code);
  return typeLabel(tx.transactionType);
}

function isOutflow(type: string): boolean {
  return type !== 'SALE' && type !== 'DEPOSIT';
}

function resetPayoutForm() {
  amount.value = null;
  description.value = '';
  referenceNumber.value = '';
}

async function loadSession(opts: { silent?: boolean } = {}) {
  if (!opts.silent) loading.value = true;
  error.value = null;
  try {
    session.value = await api.get<CashSession | null>('/staff/cash/sessions/current');
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.cash.loadFailed');
  } finally {
    loading.value = false;
  }
}

async function openSession() {
  if (openingBalance.value == null) return;
  busy.value = true;
  error.value = null;
  try {
    await api.post('/staff/cash/sessions', { openingBalance: openingBalance.value }, { successMessage: t('staff.cash.openSucceeded') });
    openingBalance.value = null;
    await loadSession({ silent: true });
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.cash.openFailed');
  } finally {
    busy.value = false;
  }
}

async function closeSession() {
  if (!session.value || closingBalance.value == null) return;
  busy.value = true;
  error.value = null;
  try {
    await api.post(
      `/staff/cash/sessions/${session.value.id}/close`,
      { closingBalance: closingBalance.value },
      {
        successMessage: canFinalizeClose.value ? t('staff.cash.closeSucceeded') : t('staff.cash.submitSucceeded'),
      },
    );
    closingBalance.value = null;
    await loadSession({ silent: true });
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.cash.closeFailed');
  } finally {
    busy.value = false;
  }
}

async function recordPayout() {
  if (!session.value || amount.value == null || amount.value <= 0) {
    error.value = t('staff.cash.amountRequired');
    return;
  }
  if (payoutKind.value !== 'DEPOSIT' && amount.value > cashInHand.value + 0.01) {
    error.value = t('staff.cash.notEnoughCash', { have: cashInHand.value.toLocaleString(), need: amount.value.toLocaleString() });
    return;
  }
  if (payoutKind.value !== 'DEPOSIT' && description.value.trim().length < 3) {
    error.value = t('staff.cash.reasonRequired');
    return;
  }

  recording.value = true;
  error.value = null;
  try {
    const successKey =
      payoutKind.value === 'WITHDRAWAL'
        ? 'staff.cash.successWithdrawal'
        : payoutKind.value === 'DEPOSIT'
          ? 'staff.cash.successDeposit'
          : 'staff.cash.successExpense';
    await api.post(
      '/staff/cash/transactions',
      {
        transactionType: payoutKind.value,
        amount: amount.value,
        category: payoutKind.value === 'EXPENSE' ? category.value : undefined,
        description: description.value.trim() || undefined,
        referenceNumber: referenceNumber.value.trim() || undefined,
      },
      { successMessage: t(successKey) },
    );
    resetPayoutForm();
    await loadSession({ silent: true });
  } catch (e) {
    error.value = e instanceof Error ? e.message : t('staff.cash.recordFailed');
  } finally {
    recording.value = false;
  }
}

const liveRefresh = useLiveRefresh('/staff/events', () => loadSession({ silent: true }), ['cash_session_updated']);
onMounted(() => {
  loadSession();
  liveRefresh.start();
});
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.nav.cash')" :subtitle="$t('staff.pages.cashSub')" />

    <p v-if="error" class="staff-error">{{ error }}</p>
    <div v-if="loading" class="staff-empty">{{ $t('staff.cash.loading') }}</div>

    <template v-else-if="session">
      <div v-if="awaitingClose" class="staff-card mb-5 border-amber-200 bg-amber-50">
        <p class="font-display font-bold text-ink">{{ $t('staff.cash.closePending') }}</p>
        <p class="mt-1 text-sm text-ink-muted">{{ $t('staff.cash.closePendingHint') }}</p>
        <p v-if="countedCash != null" class="mt-3 text-sm text-ink">
          {{ $t('staff.cash.closePendingCount', { counted: countedCash.toLocaleString(), expected: expectedCash.toLocaleString() }) }}
        </p>
      </div>

      <div class="grid gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(16rem,0.8fr)]">
        <form v-if="!awaitingClose" class="staff-card" @submit.prevent="recordPayout">
          <h2 class="font-display text-base font-bold text-ink">{{ $t('staff.cash.payFromDrawer') }}</h2>
          <p class="mt-1 text-sm text-ink-muted">{{ kindHint }}</p>

          <div class="mt-4 flex flex-wrap gap-2">
            <button
              v-for="kind in (['EXPENSE', 'WITHDRAWAL', 'DEPOSIT'] as const)"
              :key="kind"
              type="button"
              class="rounded-full px-3 py-1.5 text-xs font-bold transition"
              :class="payoutKind === kind ? 'bg-forest text-white' : 'border border-[var(--line)] bg-white text-ink hover:border-forest'"
              @click="payoutKind = kind"
            >
              {{ $t(`staff.cash.kind.${kind}`) }}
            </button>
          </div>

          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div v-if="payoutKind === 'EXPENSE'">
              <label class="mb-1 block text-xs font-medium text-ink-muted" for="cash-category">{{ $t('staff.cash.category') }}</label>
              <select id="cash-category" v-model="category" class="staff-select w-full">
                <option v-for="code in EXPENSE_CATEGORIES" :key="code" :value="code">
                  {{ $t(`staff.cash.categories.${code}`) }}
                </option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-ink-muted" for="cash-amount">{{ $t('staff.cash.amount') }}</label>
              <input
                id="cash-amount"
                v-model.number="amount"
                type="number"
                min="0.01"
                step="0.01"
                class="staff-input"
                required
              />
            </div>
          </div>

          <div class="mt-3">
            <label class="mb-1 block text-xs font-medium text-ink-muted" for="cash-reason">{{ $t('staff.cash.reason') }}</label>
            <textarea
              id="cash-reason"
              v-model="description"
              rows="2"
              class="staff-input"
              :placeholder="reasonPlaceholder"
              :required="payoutKind !== 'DEPOSIT'"
            />
          </div>

          <div class="mt-3">
            <label class="mb-1 block text-xs font-medium text-ink-muted" for="cash-ref">{{ $t('staff.cash.receipt') }}</label>
            <input id="cash-ref" v-model="referenceNumber" type="text" maxlength="100" class="staff-input" />
          </div>

          <div class="mt-4">
            <BaseButton type="submit" :disabled="recording || busy">{{ recordLabel }}</BaseButton>
          </div>
        </form>

        <div class="space-y-4">
          <div class="staff-card">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ $t('staff.cash.cashInHand') }}</p>
            <p class="font-display text-2xl font-bold text-ink">{{ cashInHand.toLocaleString() }} RWF</p>
            <p class="mt-3 text-sm text-ink-muted">{{ $t('staff.cash.openingBalance') }}</p>
            <p class="font-display text-lg font-bold text-ink">{{ Number(session.openingBalance).toLocaleString() }} RWF</p>
          </div>

          <form v-if="!awaitingClose" class="staff-card" @submit.prevent="closeSession">
            <p class="text-sm font-semibold text-ink">
              {{ canFinalizeClose ? $t('staff.cash.closeSession') : $t('staff.cash.submitClose') }}
            </p>
            <p class="mt-1 text-xs text-ink-muted">
              {{ canFinalizeClose ? $t('staff.cash.closeHint') : $t('staff.cash.submitCloseHint') }}
            </p>
            <label class="mb-1 mt-3 block text-xs font-medium text-ink-muted" for="cash-closing">{{ $t('staff.cash.closingBalanceLabel') }}</label>
            <input id="cash-closing" v-model.number="closingBalance" type="number" step="0.01" min="0" class="staff-input" required />
            <div class="mt-3">
              <BaseButton type="submit" variant="danger" :disabled="busy || recording">
                {{ canFinalizeClose ? $t('staff.cash.closeSession') : $t('staff.cash.submitClose') }}
              </BaseButton>
            </div>
          </form>
        </div>
      </div>

      <h2 class="mb-2 mt-6 font-display text-sm font-bold text-ink">{{ $t('staff.cash.transactions') }}</h2>
      <ul class="staff-card divide-y divide-[var(--line)] p-0">
        <li v-if="session.transactions.length === 0" class="px-4 py-6 text-sm text-ink-muted">{{ $t('staff.cash.noTransactions') }}</li>
        <li v-for="tx in session.transactions" :key="tx.id" class="flex items-start justify-between gap-3 px-4 py-3 text-sm">
          <div class="min-w-0">
            <p class="font-semibold text-ink">{{ txTitle(tx) }}</p>
            <p v-if="displayDescription(tx)" class="truncate text-ink-muted">{{ displayDescription(tx) }}</p>
            <p v-if="tx.referenceNumber" class="text-xs text-ink-muted">{{ $t('staff.cash.receiptShort') }}: {{ tx.referenceNumber }}</p>
            <p class="text-[11px] text-ink-muted">{{ new Date(tx.createdAt).toLocaleString() }}</p>
          </div>
          <p class="shrink-0 font-display font-bold" :class="isOutflow(tx.transactionType) ? 'text-red-700' : 'text-emerald-700'">
            {{ isOutflow(tx.transactionType) ? '−' : '+' }}{{ Number(tx.amount).toLocaleString() }}
          </p>
        </li>
      </ul>
    </template>

    <form v-else class="staff-card max-w-md" @submit.prevent="openSession">
      <p class="text-sm text-ink-muted">{{ $t('staff.cash.openHint') }}</p>
      <div class="mt-4">
        <label class="mb-1 block text-xs font-medium text-ink-muted" for="cash-opening">{{ $t('staff.cash.openingBalance') }}</label>
        <input id="cash-opening" v-model.number="openingBalance" type="number" step="0.01" min="0" class="staff-input" required />
      </div>
      <div class="mt-4">
        <BaseButton type="submit" :disabled="busy">{{ $t('staff.cash.openSession') }}</BaseButton>
      </div>
    </form>
  </div>
</template>
