import { AdjustmentType, OrderStatus } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { notifyRoles, notifyRolesExcept, notifyUser } from '@/modules/notifications/notification.service';
import { canRefundPayment } from '@/modules/orders/order.state';
import { BadRequest, Conflict, NotFound } from '@/utils/httpError';

/**
 * Two-person approval for discounts: waiters (and anyone without
 * `approve_actions`) always queue PENDING — the total is never reduced
 * until a manager/admin confirms. Staff with `approve_actions` apply
 * immediately because they *are* the confirming authority.
 */
export async function requestDiscount(
  restaurantId: string,
  orderId: string,
  staffId: string,
  canAutoApprove: boolean,
  discountPercent: number,
  reason: string,
) {
  const order = await prisma.order.findFirst({ where: { id: orderId, restaurantId } });
  if (!order) throw NotFound('Order not found');
  if (order.paymentStatus === 'PAID') throw Conflict('Cannot discount an already-paid order');

  const alreadyPending = await prisma.orderAdjustment.findFirst({
    where: { orderId, adjustmentType: AdjustmentType.DISCOUNT, status: 'PENDING' },
  });
  if (alreadyPending) throw Conflict('A discount is already waiting for approval on this order');

  const amount = Number(order.totalAmount) * (discountPercent / 100);
  const autoApproved = canAutoApprove;

  const adjustment = await prisma.orderAdjustment.create({
    data: {
      orderId,
      adjustmentType: AdjustmentType.DISCOUNT,
      amount,
      reason,
      requestedById: staffId,
      status: autoApproved ? 'APPROVED' : 'PENDING',
      approvedById: autoApproved ? staffId : null,
      approvedAt: autoApproved ? new Date() : null,
    },
  });

  if (autoApproved) {
    await prisma.order.update({ where: { id: orderId }, data: { totalAmount: { decrement: amount } } });
  } else {
    await notifyRoles(
      restaurantId,
      ['MANAGER', 'ADMIN'],
      'approval_needed',
      'Discount needs approval',
      `${discountPercent}% (${Math.round(amount).toLocaleString()} RWF) on ${order.orderNumber} is waiting for a manager`,
      { adjustmentId: adjustment.id, orderId, kind: 'discount', orderNumber: order.orderNumber, amount: Math.round(amount), percent: discountPercent },
    );
  }

  await prisma.auditTrail.create({
    data: {
      restaurantId,
      staffId,
      actionType: autoApproved ? 'apply_discount' : 'request_discount',
      tableName: 'order_adjustments',
      recordId: adjustment.id,
      reason,
      oldValue: String(order.totalAmount),
      newValue: autoApproved ? String(Number(order.totalAmount) - amount) : undefined,
      requiresApproval: !autoApproved,
      status: autoApproved ? 'APPROVED' : 'PENDING',
    },
  });

  return adjustment;
}

export async function requestRefund(restaurantId: string, orderId: string, staffId: string, canApproveRefunds: boolean, amount: number, reason: string) {
  const order = await prisma.order.findFirst({
    where: { id: orderId, restaurantId },
    include: { payments: { orderBy: { paymentDate: 'desc' }, take: 1, select: { paymentDate: true } } },
  });
  if (!order) throw NotFound('Order not found');
  if (amount > Number(order.paidAmount)) throw BadRequest('Refund amount cannot exceed the amount actually paid');
  assertRefundStillInWindow(order.paidAt, order.payments[0]?.paymentDate ?? null);


  const adjustment = await prisma.orderAdjustment.create({
    data: {
      orderId,
      adjustmentType: AdjustmentType.REFUND,
      amount,
      reason,
      requestedById: staffId,
      status: canApproveRefunds ? 'APPROVED' : 'PENDING',
      approvedById: canApproveRefunds ? staffId : null,
      approvedAt: canApproveRefunds ? new Date() : null,
    },
  });

  if (canApproveRefunds) {
    await applyRefund(orderId, amount);
  } else {
    await notifyRoles(
      restaurantId,
      ['MANAGER', 'ADMIN'],
      'approval_needed',
      'Refund needs approval',
      `${amount.toLocaleString()} RWF refund on ${order.orderNumber} is waiting for a manager`,
      { adjustmentId: adjustment.id, orderId, kind: 'refund', orderNumber: order.orderNumber, amount },
    );
  }

  await prisma.auditTrail.create({
    data: {
      restaurantId,
      staffId,
      actionType: canApproveRefunds ? 'apply_refund' : 'request_refund',
      tableName: 'order_adjustments',
      recordId: adjustment.id,
      reason,
      oldValue: String(order.paidAmount),
      requiresApproval: !canApproveRefunds,
      status: canApproveRefunds ? 'APPROVED' : 'PENDING',
    },
  });

  return adjustment;
}

async function applyRefund(orderId: string, amount: number) {
  const order = await prisma.order.findUniqueOrThrow({ where: { id: orderId } });
  const newPaidAmount = Number(order.paidAmount) - amount;
  await prisma.order.update({
    where: { id: orderId },
    data: { paidAmount: newPaidAmount, paymentStatus: newPaidAmount <= 0 ? 'REFUNDED' : 'PARTIAL' },
  });
}

function assertRefundStillInWindow(paidAt: Date | null, latestPaymentDate: Date | null) {
  if (!canRefundPayment(paidAt, latestPaymentDate)) {
    throw Conflict('Refunds are only allowed within 24 hours of payment');
  }
}

export async function listPendingApprovals(restaurantId: string) {
  const pending = await prisma.orderAdjustment.findMany({
    where: { status: 'PENDING', order: { restaurantId } },
    include: {
      order: {
        select: {
          orderNumber: true,
          paymentStatus: true,
          totalAmount: true,
          paidAmount: true,
          table: { select: { tableNumber: true } },
        },
      },
    },
    orderBy: { createdAt: 'desc' },
  });

  const staffIds = [...new Set(pending.map((p) => p.requestedById))];
  const staff = staffIds.length
    ? await prisma.staffUser.findMany({ where: { id: { in: staffIds } }, select: { id: true, fullName: true } })
    : [];
  const names = Object.fromEntries(staff.map((s) => [s.id, s.fullName]));

  // Gives the manager the requester's full current workload alongside the
  // adjustment itself — an on-shift waiter asking for a discount while
  // already juggling several other open tables is useful approval context.
  const onShiftStaffIds = staffIds.length
    ? new Set(
        (
          await prisma.staffShift.findMany({
            where: { staffId: { in: staffIds }, clockOut: null, status: 'ACTIVE' },
            select: { staffId: true },
          })
        ).map((s) => s.staffId),
      )
    : new Set<string>();

  const activeOrders = staffIds.length
    ? await prisma.order.findMany({
        where: {
          restaurantId,
          status: { notIn: [OrderStatus.COMPLETED, OrderStatus.CANCELLED] },
          OR: [{ confirmedById: { in: staffIds } }, { servedById: { in: staffIds } }, { createdByStaffId: { in: staffIds } }],
        },
        select: {
          id: true,
          orderNumber: true,
          status: true,
          confirmedById: true,
          servedById: true,
          createdByStaffId: true,
          table: { select: { tableNumber: true } },
        },
      })
    : [];

  const ordersByStaff = new Map<string, { id: string; orderNumber: string; status: OrderStatus; tableNumber: string }[]>();
  for (const order of activeOrders) {
    const assignedIds = new Set(
      [order.confirmedById, order.servedById, order.createdByStaffId].filter(
        (id): id is string => Boolean(id) && staffIds.includes(id as string),
      ),
    );
    for (const staffId of assignedIds) {
      const list = ordersByStaff.get(staffId) ?? [];
      list.push({ id: order.id, orderNumber: order.orderNumber, status: order.status, tableNumber: order.table.tableNumber });
      ordersByStaff.set(staffId, list);
    }
  }

  return pending.map((p) => ({
    id: p.id,
    kind: p.adjustmentType,
    orderId: p.orderId,
    adjustmentType: p.adjustmentType,
    amount: Number(p.amount),
    reason: p.reason,
    status: p.status,
    requestedById: p.requestedById,
    requestedByName: names[p.requestedById] ?? 'Unknown',
    requestedByOnShift: onShiftStaffIds.has(p.requestedById),
    requestedByActiveOrders: (ordersByStaff.get(p.requestedById) ?? []).filter((o) => o.id !== p.orderId),
    createdAt: p.createdAt,
    order: {
      orderNumber: p.order.orderNumber,
      paymentStatus: p.order.paymentStatus,
      totalAmount: Number(p.order.totalAmount),
      paidAmount: Number(p.order.paidAmount),
      tableNumber: p.order.table.tableNumber,
    },
  }));
}

export async function approveAdjustment(restaurantId: string, adjustmentId: string, approvedById: string) {
  const adjustment = await prisma.orderAdjustment.findFirst({
    where: { id: adjustmentId, order: { restaurantId } },
    include: { order: true },
  });
  if (!adjustment) throw NotFound('Adjustment not found');
  if (adjustment.status !== 'PENDING') throw Conflict('Adjustment already resolved');
  if (adjustment.requestedById === approvedById) throw Conflict('You cannot approve your own request — this needs a second person');

  await prisma.orderAdjustment.update({ where: { id: adjustmentId }, data: { status: 'APPROVED', approvedById, approvedAt: new Date() } });

  if (adjustment.adjustmentType === 'DISCOUNT') {
    await prisma.order.update({ where: { id: adjustment.orderId }, data: { totalAmount: { decrement: adjustment.amount } } });
  } else if (adjustment.adjustmentType === 'REFUND') {
    const latestPayment = await prisma.payment.findFirst({
      where: { orderId: adjustment.orderId },
      orderBy: { paymentDate: 'desc' },
      select: { paymentDate: true },
    });
    assertRefundStillInWindow(adjustment.order.paidAt, latestPayment?.paymentDate ?? null);
    await applyRefund(adjustment.orderId, Number(adjustment.amount));
  }

  await prisma.auditTrail.create({
    data: {
      restaurantId,
      staffId: approvedById,
      actionType: adjustment.adjustmentType === 'DISCOUNT' ? 'approve_discount' : 'approve_refund',
      tableName: 'order_adjustments',
      recordId: adjustment.id,
      reason: adjustment.reason,
    },
  });

  await notifyUser(
    restaurantId,
    adjustment.requestedById,
    'approval_resolved',
    `${adjustment.adjustmentType === 'DISCOUNT' ? 'Discount' : 'Refund'} approved`,
    `Your ${adjustment.adjustmentType.toLowerCase()} of ${Number(adjustment.amount).toLocaleString()} RWF on ${adjustment.order.orderNumber} was approved`,
    {
      adjustmentId: adjustment.id,
      orderId: adjustment.orderId,
      kind: adjustment.adjustmentType === 'DISCOUNT' ? 'discount' : 'refund',
      decision: 'approved',
      orderNumber: adjustment.order.orderNumber,
      amount: Number(adjustment.amount),
    },
  );
}

export async function rejectAdjustment(restaurantId: string, adjustmentId: string, approvedById: string) {
  const adjustment = await prisma.orderAdjustment.findFirst({
    where: { id: adjustmentId, order: { restaurantId } },
    include: { order: true },
  });
  if (!adjustment) throw NotFound('Adjustment not found');
  if (adjustment.status !== 'PENDING') throw Conflict('Adjustment already resolved');

  await prisma.orderAdjustment.update({ where: { id: adjustmentId }, data: { status: 'REJECTED', approvedById, approvedAt: new Date() } });

  await prisma.auditTrail.create({
    data: {
      restaurantId,
      staffId: approvedById,
      actionType: adjustment.adjustmentType === 'DISCOUNT' ? 'reject_discount' : 'reject_refund',
      tableName: 'order_adjustments',
      recordId: adjustment.id,
      reason: adjustment.reason,
    },
  });

  const kind = adjustment.adjustmentType === 'DISCOUNT' ? 'discount' : 'refund';
  const amount = Number(adjustment.amount);
  const orderNumber = adjustment.order.orderNumber;
  const title = `${adjustment.adjustmentType === 'DISCOUNT' ? 'Discount' : 'Refund'} rejected`;
  const payload = {
    adjustmentId: adjustment.id,
    orderId: adjustment.orderId,
    kind,
    decision: 'rejected' as const,
    orderNumber,
    amount,
  };

  await notifyUser(
    restaurantId,
    adjustment.requestedById,
    'approval_resolved',
    title,
    `Your ${kind} of ${amount.toLocaleString()} RWF on ${orderNumber} was rejected`,
    { ...payload, audience: 'requester' },
  );

  // Cashiers need the rejection even when a waiter/manager requested it —
  // they are the ones collecting payment and must not honour a refused discount/refund.
  await notifyRolesExcept(
    restaurantId,
    ['CASHIER'],
    adjustment.requestedById,
    'approval_resolved',
    title,
    `${kind} of ${amount.toLocaleString()} RWF on ${orderNumber} was rejected`,
    { ...payload, audience: 'cashier' },
  );
}
