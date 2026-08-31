import { OrderItemStatus, OrderStatus, PaymentStatus } from '@prisma/client';

/**
 * Explicit, unit-testable order state machine.
 *
 * The legacy app derived order-level status from item-level status via ad
 * hoc SQL/PHP scattered across `updateOrderStatusFromItems()`
 * (docs/CURRENT_SYSTEM_AUDIT.md §6). This function is the single place
 * that logic lives now. Rules preserved from the legacy behavior:
 *   - all items SERVED            → order READY
 *     (legacy quirk kept intentionally: order becomes fully "served" only
 *     via the explicit waiter action, not automatically from item state —
 *     see `allowedOrderTransitions` below)
 *   - any item READY or SERVED    → order READY
 *   - any item PREPARING          → order PREPARING
 *   - all items still PENDING     → order CONFIRMED
 */
export function deriveOrderStatusFromItems(itemStatuses: OrderItemStatus[]): OrderStatus {
  if (itemStatuses.length === 0) return OrderStatus.CONFIRMED;

  if (itemStatuses.every((s) => s === OrderItemStatus.SERVED)) return OrderStatus.READY;
  if (itemStatuses.some((s) => s === OrderItemStatus.READY || s === OrderItemStatus.SERVED)) return OrderStatus.READY;
  if (itemStatuses.some((s) => s === OrderItemStatus.PREPARING)) return OrderStatus.PREPARING;
  return OrderStatus.CONFIRMED;
}

/**
 * Role-gated order-level transitions, ported from the legacy
 * `$allowedTransitions` map in api.php (waiter-facing transitions only —
 * kitchen only ever moves item-level status, see deriveOrderStatusFromItems
 * above; payment completion is a separate forced transition, see
 * order.service.ts::recordPayment).
 */
export const waiterAllowedOrderTransitions: Record<OrderStatus, OrderStatus[]> = {
  [OrderStatus.PENDING]: [OrderStatus.CONFIRMED],
  [OrderStatus.CONFIRMED]: [],
  [OrderStatus.PREPARING]: [],
  [OrderStatus.READY]: [OrderStatus.COMPLETED],
  [OrderStatus.SERVED]: [OrderStatus.COMPLETED],
  [OrderStatus.COMPLETED]: [],
  [OrderStatus.CANCELLED]: [],
};

/** 60-second cancellation window, preserved from legacy (docs/CURRENT_SYSTEM_AUDIT.md §5). */
export const ORDER_CANCELLATION_WINDOW_SECONDS = 60;

export function canCancelOrder(status: OrderStatus, createdAt: Date, now: Date = new Date()): boolean {
  if (status !== OrderStatus.PENDING) return false;
  const elapsedSeconds = (now.getTime() - createdAt.getTime()) / 1000;
  return elapsedSeconds <= ORDER_CANCELLATION_WINDOW_SECONDS;
}

/**
 * Delay-escalation tiers, preserved from legacy `checkDelayedOrders`
 * (docs/CURRENT_SYSTEM_AUDIT.md line 129 flags these as hardcoded numbers
 * that should become configurable settings eventually — kept as constants
 * for now, same as the legacy behavior being ported).
 */
export const DELAY_REMINDER_MINUTES = 5;
export const DELAY_ESCALATION_MINUTES = 10;
export const DEFAULT_PREP_TIME_MINUTES = 15;

/** Payments older than this cannot be refunded. */
export const REFUND_WINDOW_MS = 24 * 60 * 60 * 1000;

export function canRefundPayment(
  paidAt: Date | null,
  latestPaymentDate: Date | null,
  now: Date = new Date(),
): boolean {
  const origin = paidAt ?? latestPaymentDate;
  if (!origin) return false;
  return now.getTime() - origin.getTime() <= REFUND_WINDOW_MS;
}

/**
 * Category-name heuristic for the "waiter beverage exception", ported
 * verbatim from legacy `updateOrderItemStatus` (app/controllers/api.php):
 * waiters may update beverage-category items directly (pour a drink, mark
 * it served) without waiting on the kitchen, since drinks don't go through
 * the prep queue the way food does. There's no dedicated `isBeverage` flag
 * on MenuCategory — matching by name is what legacy did, so this keeps that
 * behavior rather than introducing a schema change beyond what's needed.
 */
const BEVERAGE_CATEGORY_NAMES = new Set(['beverages', 'beverage', 'drinks', 'drink', 'beer', 'wine', 'cocktails', 'soft drinks']);

export function isBeverageCategory(categoryName: string | null | undefined): boolean {
  return BEVERAGE_CATEGORY_NAMES.has((categoryName ?? '').trim().toLowerCase());
}

/**
 * After this order is fully paid (or cancelled), free the table only when
 * no other non-cancelled tickets on it still have an outstanding balance.
 */
export function shouldReleaseTableAfterSettlement(
  otherOrders: { status: OrderStatus; paymentStatus: PaymentStatus }[],
): boolean {
  return !otherOrders.some(
    (order) =>
      order.status !== OrderStatus.CANCELLED &&
      (order.paymentStatus === PaymentStatus.UNPAID || order.paymentStatus === PaymentStatus.PARTIAL),
  );
}
