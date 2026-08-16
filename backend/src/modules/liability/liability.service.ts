import { OrderStatus, PaymentStatus, Prisma } from '@prisma/client';
import { prisma } from '@/config/prisma';

/**
 * Waiter liability — a real anti-theft/accountability feature, not
 * decorative (docs/CURRENT_SYSTEM_AUDIT.md §5). Whenever an order is
 * served/completed while still unpaid, the responsible staff member is
 * held financially accountable until the order is paid, written off as a
 * loss, or waived by a manager.
 *
 * The legacy app implemented this auto-creation logic TWICE
 * (Order.php and Staff.php) with two DIFFERENT priority orders for
 * resolving "who's responsible" — a real, documented bug
 * (docs/CURRENT_SYSTEM_AUDIT.md §5, §"Key Risks"). This module is the
 * single place that logic lives now.
 */
export async function evaluateOnStatusChange(
  tx: Prisma.TransactionClient,
  orderId: string,
  newStatus: OrderStatus,
): Promise<void> {
  if (newStatus !== OrderStatus.SERVED && newStatus !== OrderStatus.COMPLETED) return;

  const order = await tx.order.findUniqueOrThrow({ where: { id: orderId } });
  if (order.paymentStatus === PaymentStatus.PAID) return;
  if (order.liabilityRecorded) return;

  // Priority: server → confirmer → order creator. Preserves the legacy
  // Order.php priority chain (the one of the two divergent legacy
  // implementations that most directly reflects "who physically handled
  // the unpaid order").
  const responsibleStaffId = order.servedById ?? order.confirmedById ?? order.createdByStaffId;
  if (!responsibleStaffId) return;

  const existing = await tx.waiterLiability.findUnique({ where: { orderId } });
  if (existing) return;

  await tx.waiterLiability.create({
    data: {
      restaurantId: order.restaurantId,
      orderId: order.id,
      waiterId: responsibleStaffId,
      orderAmount: order.totalAmount,
    },
  });

  await tx.order.update({ where: { id: order.id }, data: { liabilityRecorded: true } });

  await tx.auditTrail.create({
    data: {
      restaurantId: order.restaurantId,
      staffId: responsibleStaffId,
      actionType: 'create_liability',
      tableName: 'waiter_liabilities',
      recordId: order.id,
      reason: `Order ${order.orderNumber} ${newStatus.toLowerCase()} while unpaid`,
    },
  });
}

export async function clearOnPayment(tx: Prisma.TransactionClient, orderId: string, clearedById: string, paymentMethod: string): Promise<void> {
  const liability = await tx.waiterLiability.findUnique({ where: { orderId } });
  if (!liability || liability.status !== 'ACTIVE') return;

  await tx.waiterLiability.update({
    where: { id: liability.id },
    data: {
      status: 'CLEARED',
      liabilityClearedAt: new Date(),
      clearedById,
      paymentMethod,
    },
  });
}

/** 10,000 RWF waiver-approval threshold, preserved from legacy (docs/CURRENT_SYSTEM_AUDIT.md §5). */
export const LIABILITY_WAIVER_APPROVAL_THRESHOLD = 10_000;

export async function waiveLiability(liabilityId: string, waivedById: string, reason: string) {
  return prisma.$transaction(async (tx) => {
    const liability = await tx.waiterLiability.findUniqueOrThrow({ where: { id: liabilityId } });
    const requiresApproval = Number(liability.orderAmount) > LIABILITY_WAIVER_APPROVAL_THRESHOLD;

    await tx.waiterLiability.update({
      where: { id: liabilityId },
      data: { status: 'WAIVED', clearedById: waivedById, notes: reason },
    });
    await tx.order.update({ where: { id: liability.orderId }, data: { liabilityWaived: true } });

    await tx.auditTrail.create({
      data: {
        restaurantId: liability.restaurantId,
        staffId: waivedById,
        actionType: 'waive_liability',
        tableName: 'waiter_liabilities',
        recordId: liabilityId,
        reason,
        requiresApproval,
        status: requiresApproval ? 'PENDING' : 'APPROVED',
      },
    });
  });
}

/** 120-minute abandoned-order auto-loss window, preserved from legacy. */
export const ABANDONED_ORDER_TIMEOUT_MINUTES = 120;

export async function detectAbandonedOrders(restaurantId: string): Promise<number> {
  const cutoff = new Date(Date.now() - ABANDONED_ORDER_TIMEOUT_MINUTES * 60 * 1000);

  const stale = await prisma.waiterLiability.findMany({
    where: {
      restaurantId,
      status: 'ACTIVE',
      liabilityCreatedAt: { lt: cutoff },
      order: { table: { status: 'AVAILABLE' } },
    },
  });

  for (const liability of stale) {
    await prisma.waiterLiability.update({ where: { id: liability.id }, data: { status: 'LOSS' } });
  }

  return stale.length;
}
