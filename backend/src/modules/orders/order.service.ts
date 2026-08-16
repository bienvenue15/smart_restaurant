import { OrderItemStatus, OrderStatus, PaymentMethod, PaymentStatus } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { BadRequest, Conflict, NotFound } from '@/utils/httpError';
import * as liabilityService from '@/modules/liability/liability.service';
import { LIMIT_CHECKERS } from '@/modules/subscriptions/subscription.service';
import { notifyRoles } from '@/modules/notifications/notification.service';
import { canCancelOrder, deriveOrderStatusFromItems, waiterAllowedOrderTransitions } from './order.state';

interface CreateOrderItemInput {
  menuItemId: string;
  quantity: number;
  specialRequest?: string;
}

/**
 * Order creation is the fix for the single most important finding in the
 * audit: the legacy customer-facing create_order endpoint trusted a
 * client-supplied `price` per item, validated only for numeric range
 * (docs/SECURITY_AUDIT.md #2). Here, unit price is ALWAYS looked up
 * server-side from the current MenuItem record — any price sent by the
 * client is ignored entirely (the input type above doesn't even accept one).
 */
export async function createOrder(restaurantId: string, tableId: string, items: CreateOrderItemInput[], specialInstructions?: string) {
  if (items.length === 0) throw BadRequest('Order must contain at least one item');
  await LIMIT_CHECKERS.ordersPerMonth(restaurantId);

  return prisma.$transaction(async (tx) => {
    const menuItemIds = items.map((i) => i.menuItemId);
    const menuItems = await tx.menuItem.findMany({
      where: { id: { in: menuItemIds }, restaurantId, isAvailable: true },
    });

    const menuItemById = new Map(menuItems.map((m) => [m.id, m]));
    for (const item of items) {
      if (!menuItemById.has(item.menuItemId)) {
        throw BadRequest(`Menu item ${item.menuItemId} is not available`);
      }
      if (item.quantity < 1 || item.quantity > 100) {
        throw BadRequest('Quantity must be between 1 and 100');
      }
    }

    const totalAmount = items.reduce((sum, item) => {
      const menuItem = menuItemById.get(item.menuItemId)!;
      return sum + Number(menuItem.price) * item.quantity;
    }, 0);

    const order = await tx.order.create({
      data: {
        restaurantId,
        tableId,
        orderNumber: generateOrderNumber(),
        status: OrderStatus.PENDING,
        totalAmount,
        specialInstructions,
        items: {
          create: items.map((item) => {
            const menuItem = menuItemById.get(item.menuItemId)!;
            return {
              menuItemId: item.menuItemId,
              quantity: item.quantity,
              unitPrice: menuItem.price,
              subtotal: Number(menuItem.price) * item.quantity,
              specialRequest: item.specialRequest,
            };
          }),
        },
      },
      include: { items: true },
    });

    // updateMany (not update) because `status` isn't a unique field — this is a
    // conditional no-op if the table is already occupied, mirroring the legacy
    // trigger semantics (`trg_order_update_table_status` only fired when the
    // table was previously 'available').
    await tx.restaurantTable.updateMany({
      where: { id: tableId, status: 'AVAILABLE' },
      data: { status: 'OCCUPIED', lastOccupiedAt: new Date() },
    });

    return order;
  });
}

/**
 * Role-scoped order listing, ported from `Order::getOrders()`
 * (docs/CURRENT_SYSTEM_AUDIT.md §4): kitchen only sees orders a waiter has
 * already confirmed (raw PENDING orders are invisible to kitchen — they
 * haven't been accepted into the prep pipeline yet), waiters see only
 * their own orders (confirmed or served by them), everyone else sees the
 * full restaurant order list.
 */
export async function listOrders(restaurantId: string, staffId: string, role: string, statusFilter?: OrderStatus) {
  const where: Record<string, unknown> = { restaurantId };
  if (statusFilter) where.status = statusFilter;

  if (role === 'KITCHEN') {
    where.status = statusFilter ?? { in: [OrderStatus.CONFIRMED, OrderStatus.PREPARING, OrderStatus.READY] };
  } else if (role === 'WAITER') {
    where.OR = [{ confirmedById: staffId }, { servedById: staffId }, { createdByStaffId: staffId }];
  }

  return prisma.order.findMany({
    where,
    include: { items: { include: { menuItem: { select: { name: true } } } }, table: { select: { tableNumber: true } } },
    orderBy: { createdAt: 'desc' },
    take: 100,
  });
}

export async function getOrderById(restaurantId: string, orderId: string) {
  const order = await prisma.order.findFirst({
    where: { id: orderId, restaurantId },
    include: { items: { include: { menuItem: { select: { name: true } } } }, table: true, payments: true },
  });
  if (!order) throw NotFound('Order not found');
  return order;
}

function generateOrderNumber(): string {
  const now = new Date();
  const stamp = now.toISOString().replace(/[-:.TZ]/g, '').slice(0, 14);
  const rand = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
  return `ORD-${stamp}-${rand}`;
}

export async function cancelOrder(restaurantId: string, orderId: string) {
  const order = await prisma.order.findFirst({ where: { id: orderId, restaurantId } });
  if (!order) throw NotFound('Order not found');
  if (!canCancelOrder(order.status, order.createdAt)) {
    throw Conflict('Order can no longer be cancelled (60-second window elapsed or already in progress)');
  }
  return prisma.order.update({ where: { id: orderId }, data: { status: OrderStatus.CANCELLED } });
}

/** Waiter-driven order-level transition, gated by the transition table in order.state.ts. */
export async function updateOrderStatus(restaurantId: string, orderId: string, newStatus: OrderStatus, staffId: string) {
  const updated = await prisma.$transaction(async (tx) => {
    const order = await tx.order.findFirst({ where: { id: orderId, restaurantId } });
    if (!order) throw NotFound('Order not found');

    const allowed = waiterAllowedOrderTransitions[order.status];
    if (!allowed.includes(newStatus)) {
      throw Conflict(`Cannot transition order from ${order.status} to ${newStatus}`);
    }

    const data: Record<string, unknown> = { status: newStatus };
    if (newStatus === OrderStatus.CONFIRMED) {
      data.confirmedById = staffId;
      data.confirmedAt = new Date();
    }

    const result = await tx.order.update({ where: { id: orderId }, data });
    await liabilityService.evaluateOnStatusChange(tx, orderId, newStatus);
    return result;
  });

  // Kitchen is only notified once a waiter confirms an order, never on raw
  // creation — preserves the legacy's explicit design intent
  // (docs/CURRENT_SYSTEM_AUDIT.md §"Order lifecycle": "Kitchen will be
  // notified when order is confirmed by waiter, not on initial creation").
  if (newStatus === OrderStatus.CONFIRMED) {
    await notifyRoles(restaurantId, ['KITCHEN', 'MANAGER', 'ADMIN'], 'new_order', 'New order confirmed', `Order ${updated.orderNumber} is ready to prepare`);
  }

  return updated;
}

/** Kitchen/waiter item-level status update, which then re-derives the parent order's aggregate status. */
export async function updateOrderItemStatus(restaurantId: string, orderItemId: string, newStatus: OrderItemStatus, staffId: string) {
  return prisma.$transaction(async (tx) => {
    const item = await tx.orderItem.findUnique({ where: { id: orderItemId }, include: { order: true } });
    if (!item || item.order.restaurantId !== restaurantId) throw NotFound('Order item not found');

    await tx.orderItem.update({ where: { id: orderItemId }, data: { status: newStatus } });

    const siblingItems = await tx.orderItem.findMany({ where: { orderId: item.orderId } });
    const derivedStatus = deriveOrderStatusFromItems(siblingItems.map((i) => (i.id === orderItemId ? newStatus : i.status)));

    const order = await tx.order.update({ where: { id: item.orderId }, data: { status: derivedStatus } });

    if (newStatus === OrderItemStatus.SERVED) {
      await tx.order.updateMany({ where: { id: item.orderId, servedById: null }, data: { servedById: staffId, servedAt: new Date() } });
    }

    return order;
  });
}

/**
 * Payment recording — fixes legacy Critical #3: the amount is validated
 * against the order's authoritative total server-side rather than being
 * trusted and applied unconditionally (docs/SECURITY_AUDIT.md #3).
 */
export async function recordPayment(
  restaurantId: string,
  orderId: string,
  amount: number,
  receivedAmount: number,
  paymentMethod: PaymentMethod,
  receivedById: string,
  paymentReference?: string,
) {
  return prisma.$transaction(async (tx) => {
    const order = await tx.order.findFirst({ where: { id: orderId, restaurantId } });
    if (!order) throw NotFound('Order not found');
    if (order.paymentStatus === PaymentStatus.PAID) throw Conflict('Order is already fully paid');

    const outstanding = Number(order.totalAmount) - Number(order.paidAmount);
    if (amount <= 0) throw BadRequest('Payment amount must be greater than zero');
    if (amount > outstanding + 0.01) {
      throw BadRequest(`Payment amount (${amount}) exceeds the outstanding balance (${outstanding})`);
    }

    await tx.payment.create({
      data: {
        restaurantId,
        orderId,
        paymentMethod,
        amount,
        receivedAmount,
        changeAmount: Math.max(0, receivedAmount - amount),
        receivedById,
        paymentReference,
      },
    });

    const newPaidAmount = Number(order.paidAmount) + amount;
    const isFullyPaid = newPaidAmount >= Number(order.totalAmount) - 0.01;

    const updatedOrder = await tx.order.update({
      where: { id: orderId },
      data: {
        paidAmount: newPaidAmount,
        paymentStatus: isFullyPaid ? PaymentStatus.PAID : PaymentStatus.PARTIAL,
        paidAt: isFullyPaid ? new Date() : order.paidAt,
        paidToId: isFullyPaid ? receivedById : order.paidToId,
        status: isFullyPaid ? OrderStatus.COMPLETED : order.status,
      },
    });

    if (isFullyPaid) {
      await liabilityService.clearOnPayment(tx, orderId, receivedById, paymentMethod);
      await tx.restaurantTable.update({ where: { id: order.tableId }, data: { status: 'AVAILABLE' } });
    }

    await tx.auditTrail.create({
      data: {
        restaurantId,
        staffId: receivedById,
        actionType: 'payment_received',
        tableName: 'payments',
        recordId: orderId,
      },
    });

    return updatedOrder;
  });
}
