export const EXPENSE_CATEGORIES = [
  'electricity',
  'water',
  'gas',
  'internet',
  'supplies',
  'maintenance',
  'transport',
  'other',
] as const;

export type ExpenseCategory = (typeof EXPENSE_CATEGORIES)[number];

/** Types a cashier may post from the till. Sales and refunds come from orders. */
export const CASHIER_LEDGER_TYPES = ['EXPENSE', 'WITHDRAWAL', 'DEPOSIT'] as const;

export type CashierLedgerType = (typeof CASHIER_LEDGER_TYPES)[number];

/** 1,000 RWF cash-session discrepancy threshold, preserved from legacy (docs/CURRENT_SYSTEM_AUDIT.md §5). */
export const CASH_DISCREPANCY_THRESHOLD = 1_000;

export function closeOutcome(cashInHand: number, counted: number): {
  expectedBalance: number;
  variance: number;
  status: 'CLOSED' | 'DISCREPANCY';
} {
  const expectedBalance = cashInHand;
  const variance = counted - expectedBalance;
  const status = Math.abs(variance) > CASH_DISCREPANCY_THRESHOLD ? 'DISCREPANCY' : 'CLOSED';
  return { expectedBalance, variance, status };
}

export function isCashDecrement(transactionType: string): boolean {
  return transactionType !== 'SALE' && transactionType !== 'DEPOSIT';
}

export function isExpenseCategory(value: string | null | undefined): value is ExpenseCategory {
  return Boolean(value && (EXPENSE_CATEGORIES as readonly string[]).includes(value));
}

/** Allow 1 cent of float rounding so 1,000.00 vs 1,000.001 does not block a payout. */
export function hasEnoughCashInDrawer(cashInHand: number, amount: number): boolean {
  return amount <= cashInHand + 0.01;
}
