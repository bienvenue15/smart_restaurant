import { AdjustmentType } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { BadRequest, Conflict, NotFound } from '@/utils/httpError';

/**
 * Two-person approval workflow for discounts/refunds, ported from the
 * legacy's `staff_request_discount`/`staff_request_refund` (auto-approved
 * if within the requesting staff member's limit, otherwise queued as
 * PENDING for a manager, docs/CURRENT_SYSTEM_AUDIT.md §"API endpoint
 * inventory"). Not built in the first backend pass — closing that gap here.
 */
export async function requestDiscount(
  restaurantId: string,
  orderId: string,
  staffId: string,
  staffMaxDiscountPercent: number,
  discountPercent: number,
  reason: string,
) {
  const order = await prisma.order.findFirst({ where: { id: orderId, restaurantId } });
  if (!order) throw NotFound('Order not found');
  if (order.paymentStatus === 'PAID') throw Conflict('Cannot discount an already-paid order');

  const amount = Number(order.totalAmount) * (discountPercent / 100);
  const autoApproved = discountPercent <= staffMaxDiscountPercent;

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
  }

  return adjustment;
}

export async function requestRefund(restaurantId: string, orderId: string, staffId: string, canApproveRefunds: boolean, amount: number, reason: string) {
  const order = await prisma.order.findFirst({ where: { id: orderId, restaurantId } });
  if (!order) throw NotFound('Order not found');
  if (amount > Number(order.paidAmount)) throw BadRequest('Refund amount cannot exceed the amount actually paid');

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
  }

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

export async function listPendingApprovals(restaurantId: string) {
  return prisma.orderAdjustment.findMany({
    where: { status: 'PENDING', order: { restaurantId } },
    include: { order: { select: { orderNumber: true } } },
    orderBy: { createdAt: 'desc' },
  });
}

export async function approveAdjustment(restaurantId: string, adjustmentId: string, approvedById: string) {
  const adjustment = await prisma.orderAdjustment.findFirst({
    where: { id: adjustmentId, order: { restaurantId } },
    include: { order: true },
  });
  if (!adjustment) throw NotFound('Adjustment not found');
  if (adjustment.status !== 'PENDING') throw Conflict('Adjustment already resolved');

  await prisma.orderAdjustment.update({ where: { id: adjustmentId }, data: { status: 'APPROVED', approvedById, approvedAt: new Date() } });

  if (adjustment.adjustmentType === 'DISCOUNT') {
    await prisma.order.update({ where: { id: adjustment.orderId }, data: { totalAmount: { decrement: adjustment.amount } } });
  } else if (adjustment.adjustmentType === 'REFUND') {
    await applyRefund(adjustment.orderId, Number(adjustment.amount));
  }
}

export async function rejectAdjustment(restaurantId: string, adjustmentId: string, approvedById: string) {
  const adjustment = await prisma.orderAdjustment.findFirst({ where: { id: adjustmentId, order: { restaurantId } } });
  if (!adjustment) throw NotFound('Adjustment not found');
  if (adjustment.status !== 'PENDING') throw Conflict('Adjustment already resolved');

  await prisma.orderAdjustment.update({ where: { id: adjustmentId }, data: { status: 'REJECTED', approvedById, approvedAt: new Date() } });
}
