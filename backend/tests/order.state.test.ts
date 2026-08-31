import { describe, expect, it } from 'vitest';
import { OrderItemStatus, OrderStatus, PaymentStatus } from '@prisma/client';
import {
  canCancelOrder,
  canRefundPayment,
  deriveOrderStatusFromItems,
  isBeverageCategory,
  REFUND_WINDOW_MS,
  shouldReleaseTableAfterSettlement,
  waiterAllowedOrderTransitions,
} from '@/modules/orders/order.state';

describe('deriveOrderStatusFromItems', () => {
  it('returns CONFIRMED when there are no items yet', () => {
    expect(deriveOrderStatusFromItems([])).toBe(OrderStatus.CONFIRMED);
  });

  it('returns CONFIRMED while every item is still PENDING', () => {
    expect(deriveOrderStatusFromItems([OrderItemStatus.PENDING, OrderItemStatus.PENDING])).toBe(OrderStatus.CONFIRMED);
  });

  it('returns PREPARING when any item is PREPARING and none are READY/SERVED', () => {
    expect(deriveOrderStatusFromItems([OrderItemStatus.PENDING, OrderItemStatus.PREPARING])).toBe(OrderStatus.PREPARING);
  });

  it('returns READY when any item is READY', () => {
    expect(deriveOrderStatusFromItems([OrderItemStatus.PREPARING, OrderItemStatus.READY])).toBe(OrderStatus.READY);
  });

  it('returns READY when all items are SERVED', () => {
    expect(deriveOrderStatusFromItems([OrderItemStatus.SERVED, OrderItemStatus.SERVED])).toBe(OrderStatus.READY);
  });
});

describe('waiterAllowedOrderTransitions', () => {
  it('only allows PENDING -> CONFIRMED for the waiter-facing transition table', () => {
    expect(waiterAllowedOrderTransitions[OrderStatus.PENDING]).toEqual([OrderStatus.CONFIRMED]);
  });

  it('allows READY and SERVED to move to COMPLETED', () => {
    expect(waiterAllowedOrderTransitions[OrderStatus.READY]).toEqual([OrderStatus.COMPLETED]);
    expect(waiterAllowedOrderTransitions[OrderStatus.SERVED]).toEqual([OrderStatus.COMPLETED]);
  });

  it('does not allow any transition out of a terminal state', () => {
    expect(waiterAllowedOrderTransitions[OrderStatus.COMPLETED]).toEqual([]);
    expect(waiterAllowedOrderTransitions[OrderStatus.CANCELLED]).toEqual([]);
  });
});

describe('canCancelOrder', () => {
  it('allows cancellation within the 60-second window while PENDING', () => {
    const createdAt = new Date();
    const now = new Date(createdAt.getTime() + 30_000);
    expect(canCancelOrder(OrderStatus.PENDING, createdAt, now)).toBe(true);
  });

  it('blocks cancellation once the 60-second window elapses', () => {
    const createdAt = new Date();
    const now = new Date(createdAt.getTime() + 61_000);
    expect(canCancelOrder(OrderStatus.PENDING, createdAt, now)).toBe(false);
  });

  it('blocks cancellation once the order is no longer PENDING', () => {
    const createdAt = new Date();
    expect(canCancelOrder(OrderStatus.CONFIRMED, createdAt, createdAt)).toBe(false);
  });
});

describe('isBeverageCategory', () => {
  it('matches the legacy name heuristic', () => {
    expect(isBeverageCategory('Drinks')).toBe(true);
    expect(isBeverageCategory('beverages')).toBe(true);
    expect(isBeverageCategory('Mains')).toBe(false);
  });
});

describe('shouldReleaseTableAfterSettlement', () => {
  it('releases when the table has no other open unpaid orders', () => {
    expect(shouldReleaseTableAfterSettlement([])).toBe(true);
    expect(
      shouldReleaseTableAfterSettlement([
        { status: OrderStatus.COMPLETED, paymentStatus: PaymentStatus.PAID },
        { status: OrderStatus.CANCELLED, paymentStatus: PaymentStatus.UNPAID },
      ]),
    ).toBe(true);
  });

  it('keeps the table occupied when another unpaid or partial order remains', () => {
    expect(
      shouldReleaseTableAfterSettlement([{ status: OrderStatus.CONFIRMED, paymentStatus: PaymentStatus.UNPAID }]),
    ).toBe(false);
    expect(
      shouldReleaseTableAfterSettlement([{ status: OrderStatus.SERVED, paymentStatus: PaymentStatus.PARTIAL }]),
    ).toBe(false);
  });
});

describe('canRefundPayment', () => {
  it('allows a refund within 24 hours of payment', () => {
    const paidAt = new Date();
    expect(canRefundPayment(paidAt, null, new Date(paidAt.getTime() + 12 * 60 * 60 * 1000))).toBe(true);
  });

  it('blocks a refund after 24 hours', () => {
    const paidAt = new Date();
    expect(canRefundPayment(paidAt, null, new Date(paidAt.getTime() + REFUND_WINDOW_MS + 1))).toBe(false);
  });

  it('falls back to the latest payment date when paidAt is missing', () => {
    const paymentDate = new Date();
    expect(canRefundPayment(null, paymentDate, new Date(paymentDate.getTime() + 1000))).toBe(true);
    expect(canRefundPayment(null, paymentDate, new Date(paymentDate.getTime() + REFUND_WINDOW_MS + 1))).toBe(false);
  });

  it('blocks a refund when there is no payment timestamp', () => {
    expect(canRefundPayment(null, null)).toBe(false);
  });
});
