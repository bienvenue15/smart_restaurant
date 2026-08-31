import { describe, expect, it } from 'vitest';
import {
  closeOutcome,
  hasEnoughCashInDrawer,
  isCashDecrement,
  isExpenseCategory,
} from '@/modules/cash/cash.constants';

describe('hasEnoughCashInDrawer', () => {
  it('allows paying exactly the cash in hand', () => {
    expect(hasEnoughCashInDrawer(5_000, 5_000)).toBe(true);
  });

  it('blocks paying more than the drawer holds', () => {
    expect(hasEnoughCashInDrawer(5_000, 5_001)).toBe(false);
  });

  it('tolerates a cent of float rounding', () => {
    expect(hasEnoughCashInDrawer(100, 100.005)).toBe(true);
  });
});

describe('isCashDecrement', () => {
  it('treats expenses and withdrawals as money leaving the till', () => {
    expect(isCashDecrement('EXPENSE')).toBe(true);
    expect(isCashDecrement('WITHDRAWAL')).toBe(true);
    expect(isCashDecrement('SALE')).toBe(false);
    expect(isCashDecrement('DEPOSIT')).toBe(false);
  });
});

describe('closeOutcome', () => {
  it('closes cleanly when the count is within 1,000 RWF', () => {
    expect(closeOutcome(10_000, 10_500)).toEqual({
      expectedBalance: 10_000,
      variance: 500,
      status: 'CLOSED',
    });
  });

  it('flags a discrepancy beyond the 1,000 RWF threshold', () => {
    expect(closeOutcome(10_000, 11_001).status).toBe('DISCREPANCY');
  });
});

describe('isExpenseCategory', () => {
  it('accepts electricity and other operating categories', () => {
    expect(isExpenseCategory('electricity')).toBe(true);
    expect(isExpenseCategory('supplies')).toBe(true);
    expect(isExpenseCategory('payroll')).toBe(false);
    expect(isExpenseCategory(null)).toBe(false);
  });
});
