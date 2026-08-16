import { OrderItemStatus, OrderStatus } from '@prisma/client';

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
