import { GuestHeardAboutChannel, OrderItemStatus, OrderStatus, PaymentMethod, PaymentStatus, Prisma } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { BadRequest, Conflict, Forbidden, NotFound } from '@/utils/httpError';
import * as liabilityService from '@/modules/liability/liability.service';
import { assertFeature, assertSubscriptionActive, LIMIT_CHECKERS } from '@/modules/subscriptions/subscription.service';
import { notifyRoles, notifyUser } from '@/modules/notifications/notification.service';
import { notifyPhone } from '@/services/messaging.service';
import { publishRealtime } from '@/services/realtime.service';
import { customerOrderLookupWhere } from '@/utils/tenantScope';
import {
  canCancelOrder,
  deriveOrderStatusFromItems,
  DEFAULT_PREP_TIME_MINUTES,
  DELAY_ESCALATION_MINUTES,
  DELAY_REMINDER_MINUTES,
  isBeverageCategory,
  REFUND_WINDOW_MS,
  shouldReleaseTableAfterSettlement,
  waiterAllowedOrderTransitions,
} from './order.state';

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

  const order = await prisma.$transaction(async (tx) => {
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

    // Captured once and reused for both the order and the table's
    // lastOccupiedAt below — customer-facing order-history scoping
    // (listOrdersForTableSession) treats lastOccupiedAt as the start-of-session
    // boundary, so the order that itself triggers the available→occupied
    // transition must not be timestamped strictly before that boundary.
    const now = new Date();

    const order = await tx.order.create({
      data: {
        restaurantId,
        tableId,
        orderNumber: generateOrderNumber(),
        status: OrderStatus.PENDING,
        totalAmount,
        specialInstructions,
        createdAt: now,
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
      include: { items: true, table: { select: { tableNumber: true } } },
    });

    // updateMany (not update) because `status` isn't a unique field — this is a
    // conditional no-op if the table is already occupied, mirroring the legacy
    // trigger semantics (`trg_order_update_table_status` only fired when the
    // table was previously 'available').
    await tx.restaurantTable.updateMany({
      where: { id: tableId, status: 'AVAILABLE' },
      data: { status: 'OCCUPIED', lastOccupiedAt: now },
    });

    return order;
  });

  await notifyRoles(
    restaurantId,
    ['WAITER', 'MANAGER', 'ADMIN'],
    'order_received',
    'New order received',
    `Table ${order.table.tableNumber} placed order ${order.orderNumber}`,
    { orderNumber: order.orderNumber, tableNumber: order.table.tableNumber },
  );
  await publishRealtime({ channel: 'customer', type: 'order_status', restaurantId, tableId });
  await publishRealtime({ channel: 'staff', type: 'order_status', restaurantId });
  return order;
}

/**
 * Role-scoped order listing, ported from `Order::getOrders()`
 * (docs/CURRENT_SYSTEM_AUDIT.md §4): kitchen only sees orders a waiter has
 * already confirmed (raw PENDING orders are invisible to kitchen — they
 * haven't been accepted into the prep pipeline yet), waiters see only
 * their own orders (confirmed or served by them) plus unclaimed guest QR
 * orders still PENDING, so the floor can pick them up when the double-ring
 * fires; everyone else sees the full restaurant order list.
 */
/**
 * `prepQueueOnly` scopes the result to the kitchen's active prep queue
 * (CONFIRMED/PREPARING/READY) — a property of *which view is asking*
 * (kitchen.vue), not of the viewer's role. Previously this only kicked in
 * for role === 'KITCHEN', so a manager/admin/waiter opening the same
 * Kitchen page (nothing gates that route to KITCHEN-role staff) saw every
 * order regardless of status, including already-COMPLETED ones whose items
 * were never individually marked SERVED — offering a "Preparing" button
 * that always failed with a confusing "already completed" error.
 */
export async function listOrders(
  restaurantId: string,
  staffId: string,
  role: string,
  statusFilter?: OrderStatus,
  prepQueueOnly?: boolean,
) {
  const where: Record<string, unknown> = { restaurantId };
  if (statusFilter) where.status = statusFilter;

  if (role === 'KITCHEN' || prepQueueOnly) {
    where.status = statusFilter ?? { in: [OrderStatus.CONFIRMED, OrderStatus.PREPARING, OrderStatus.READY] };
  } else if (role === 'WAITER') {
    where.OR = [
      { confirmedById: staffId },
      { servedById: staffId },
      { createdByStaffId: staffId },
      { createdByStaffId: null, confirmedById: null, status: OrderStatus.PENDING },
    ];
  } else if (role === 'CASHIER') {
    const refundableSince = new Date(Date.now() - REFUND_WINDOW_MS);
    where.OR = [
      { paymentStatus: { in: [PaymentStatus.UNPAID, PaymentStatus.PARTIAL] }, status: { not: OrderStatus.CANCELLED } },
      { paymentStatus: { in: [PaymentStatus.PAID, PaymentStatus.REFUNDED] }, paidAt: { gte: refundableSince } },
    ];
  }

  return prisma.order.findMany({
    where,
    include: {
      items: { include: { menuItem: { select: { name: true, preparationTime: true, category: { select: { name: true } } } } } },
      table: { select: { tableNumber: true } },
      createdByStaff: { select: { id: true, fullName: true } },
      guestHeardAbout: { select: { skipped: true, channel: true, rating: true, comment: true } },
    },
    orderBy: { createdAt: 'desc' },
    take: 100,
  });
}

/**
 * Customer-facing order history/polling, scoped to the table the customer's
 * signed session token was issued for (never a client-supplied filter — see
 * requireCustomerSession in middleware/auth.ts). Orders don't carry an
 * explicit "session id", so we approximate "this dining session" as orders
 * placed since the table's most recent available→occupied transition,
 * which is set exactly once per new customer sitting down (order.service.ts
 * ::createOrder). This avoids surfacing a previous diner's order history to
 * the next customer seated at the same table.
 */
export async function listOrdersForTableSession(restaurantId: string, tableId: string) {
  const table = await prisma.restaurantTable.findFirst({ where: { id: tableId, restaurantId } });
  if (!table) throw NotFound('Table not found');

  return prisma.order.findMany({
    where: {
      restaurantId,
      tableId,
      ...(table.lastOccupiedAt ? { createdAt: { gte: table.lastOccupiedAt } } : {}),
    },
    include: { items: { include: { menuItem: { select: { name: true } } } }, guestHeardAbout: true },
    orderBy: { createdAt: 'desc' },
  });
}

export async function getOrderForTableSession(restaurantId: string, tableId: string, orderId: string) {
  const order = await prisma.order.findFirst({
    where: customerOrderLookupWhere({ restaurantId, tableId }, orderId),
    include: { items: { include: { menuItem: { select: { name: true } } } } },
  });
  if (!order) throw NotFound('Order not found');
  return order;
}

/**
 * Mirrors listOrders' role-scoping (§ above): a waiter must not be able to
 * pull another waiter's order details — including customer phone and
 * payment records — by guessing/reusing an order ID, even within the same
 * restaurant, just because the list view already hides it from them.
 */
export async function getOrderById(restaurantId: string, orderId: string, staffId: string, role: string) {
  const where: Record<string, unknown> = { id: orderId, restaurantId };
  if (role === 'WAITER') {
    where.OR = [
      { confirmedById: staffId },
      { servedById: staffId },
      { createdByStaffId: staffId },
      { createdByStaffId: null, confirmedById: null, status: OrderStatus.PENDING },
    ];
  }

  const order = await prisma.order.findFirst({
    where,
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

  const result = await prisma.$transaction(async (tx) => {
    const updated = await tx.order.update({ where: { id: orderId }, data: { status: OrderStatus.CANCELLED } });
    const tableReleased = await releaseTableIfNoUnpaidOrders(tx, order.tableId, orderId);
    return { updated, tableReleased };
  });

  await publishRealtime({ channel: 'customer', type: 'order_status', restaurantId, tableId: order.tableId });
  await publishRealtime({ channel: 'staff', type: 'order_status', restaurantId });
  if (result.tableReleased) {
    await publishRealtime({ channel: 'staff', type: 'table_status', restaurantId });
  }
  return result.updated;
}

const ADD_ITEMS_ALLOWED_STATUSES: OrderStatus[] = [OrderStatus.PENDING, OrderStatus.CONFIRMED];

/**
 * Customer-facing "add more items to an order I already placed", ported
 * from legacy `Order::addItemsToOrder()`. Like createOrder above, price is
 * always looked up server-side — the legacy version trusted the client's
 * `item.price` here too (docs/SECURITY_AUDIT.md #2 covers both endpoints).
 *
 * Preserves one legacy business rule as-is: adding items to an already
 * CONFIRMED order resets it to PENDING so the waiter must re-confirm (and
 * kitchen gets re-notified via the normal confirm flow) rather than new
 * items silently entering the prep queue unseen.
 */
export async function addItemsToOrder(restaurantId: string, tableId: string, orderId: string, items: CreateOrderItemInput[]) {
  const { order, wasConfirmed } = await prisma.$transaction(async (tx) => {
    const existing = await tx.order.findFirst({ where: { id: orderId, restaurantId, tableId } });
    if (!existing) throw NotFound('Order not found');
    if (!ADD_ITEMS_ALLOWED_STATUSES.includes(existing.status)) {
      throw Conflict(`Cannot add items to a ${existing.status.toLowerCase()} order`);
    }

    const menuItemIds = items.map((i) => i.menuItemId);
    const menuItems = await tx.menuItem.findMany({ where: { id: { in: menuItemIds }, restaurantId, isAvailable: true } });
    const menuItemById = new Map(menuItems.map((m) => [m.id, m]));
    for (const item of items) {
      if (!menuItemById.has(item.menuItemId)) throw BadRequest(`Menu item ${item.menuItemId} is not available`);
      if (item.quantity < 1 || item.quantity > 100) throw BadRequest('Quantity must be between 1 and 100');
    }

    const additionalAmount = items.reduce((sum, item) => {
      const menuItem = menuItemById.get(item.menuItemId)!;
      return sum + Number(menuItem.price) * item.quantity;
    }, 0);

    await tx.orderItem.createMany({
      data: items.map((item) => {
        const menuItem = menuItemById.get(item.menuItemId)!;
        return {
          orderId,
          menuItemId: item.menuItemId,
          quantity: item.quantity,
          unitPrice: menuItem.price,
          subtotal: Number(menuItem.price) * item.quantity,
          specialRequest: item.specialRequest,
        };
      }),
    });

    const wasConfirmed = existing.status === OrderStatus.CONFIRMED;
    const updated = await tx.order.update({
      where: { id: orderId },
      data: {
        totalAmount: Number(existing.totalAmount) + additionalAmount,
        ...(wasConfirmed ? { status: OrderStatus.PENDING, confirmedById: null, confirmedAt: null } : {}),
      },
      include: { items: { include: { menuItem: { select: { name: true } } } } },
    });

    return { order: updated, wasConfirmed };
  });

  await notifyRoles(
    restaurantId,
    ['WAITER', 'MANAGER', 'ADMIN'],
    'order_items_added',
    'Items added to order',
    wasConfirmed
      ? `Order ${order.orderNumber} has new items and needs to be re-confirmed`
      : `Order ${order.orderNumber} has new items`,
    { orderNumber: order.orderNumber, needsReconfirm: wasConfirmed },
  );

  return order;
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
    // Mirrors the item-level SERVED handling in updateOrderItemStatus —
    // most orders (anything not beverage-only) never hit that item-level
    // path and instead complete straight from READY here, so this was the
    // only gap where `servedById` never got set. liability.service.ts's
    // evaluateOnStatusChange prioritizes servedById over confirmedById when
    // attributing an unpaid order's liability, specifically so it lands on
    // whoever actually served/completed it rather than whoever merely
    // confirmed it earlier — that fallback was silently always firing here.
    if ((newStatus === OrderStatus.SERVED || newStatus === OrderStatus.COMPLETED) && !order.servedById) {
      data.servedById = staffId;
      data.servedAt = new Date();
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
    await notifyRoles(restaurantId, ['KITCHEN', 'MANAGER', 'ADMIN'], 'new_order', 'New order confirmed', `Order ${updated.orderNumber} is ready to prepare`, {
      orderNumber: updated.orderNumber,
    });
  }

  await publishRealtime({
    channel: 'customer',
    type: 'order_status',
    restaurantId,
    tableId: updated.tableId,
  });
  await publishRealtime({ channel: 'staff', type: 'order_status', restaurantId });

  return updated;
}

/**
 * Manager-driven reassignment of an order to a different waiter (e.g. for
 * self-service/QR orders no waiter has claimed yet), ported from legacy
 * `Order::assignWaiterToOrder`. Assigns via `createdByStaffId`, the same
 * field listOrders already scopes a waiter's "my orders" view by, so this
 * takes effect immediately without any separate assignment table.
 *
 * Preserves the legacy off-shift guard as-is: a manager cannot hand an
 * order to a waiter who isn't currently clocked in — assigning to someone
 * who won't see it defeats the point.
 */
/** On-shift waiters a manager can currently assign an order to — see assignOrderToWaiter below. */
/**
 * Feeds the manager's assignment dropdown (orders.vue) — each on-shift
 * waiter's current workload, so a manager isn't handing a table to whoever
 * happens to be listed first when someone else is already juggling five.
 */
export async function listOnShiftWaiters(restaurantId: string) {
  const waiters = await prisma.staffUser.findMany({
    where: {
      restaurantId,
      role: 'WAITER',
      isActive: true,
      shifts: { some: { clockOut: null, status: 'ACTIVE' } },
    },
    select: { id: true, fullName: true },
  });
  if (waiters.length === 0) return [];

  const waiterIds = waiters.map((w) => w.id);
  const activeOrders = await prisma.order.findMany({
    where: {
      restaurantId,
      status: { notIn: [OrderStatus.COMPLETED, OrderStatus.CANCELLED] },
      OR: [{ confirmedById: { in: waiterIds } }, { servedById: { in: waiterIds } }, { createdByStaffId: { in: waiterIds } }],
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
  });

  const ordersByWaiter = new Map<string, { id: string; orderNumber: string; status: OrderStatus; tableNumber: string }[]>();
  for (const order of activeOrders) {
    const assignedIds = new Set(
      [order.confirmedById, order.servedById, order.createdByStaffId].filter(
        (id): id is string => Boolean(id) && waiterIds.includes(id as string),
      ),
    );
    for (const waiterId of assignedIds) {
      const list = ordersByWaiter.get(waiterId) ?? [];
      list.push({ id: order.id, orderNumber: order.orderNumber, status: order.status, tableNumber: order.table.tableNumber });
      ordersByWaiter.set(waiterId, list);
    }
  }

  return waiters.map((w) => ({ id: w.id, fullName: w.fullName, activeOrders: ordersByWaiter.get(w.id) ?? [] }));
}

export async function assignOrderToWaiter(restaurantId: string, orderId: string, waiterId: string) {
  const order = await prisma.order.findFirst({ where: { id: orderId, restaurantId }, include: { table: { select: { tableNumber: true } } } });
  if (!order) throw NotFound('Order not found');

  const waiter = await prisma.staffUser.findFirst({ where: { id: waiterId, restaurantId, role: 'WAITER', isActive: true } });
  if (!waiter) throw NotFound('Waiter not found');

  const openShift = await prisma.staffShift.findFirst({ where: { staffId: waiterId, clockOut: null, status: 'ACTIVE' } });
  if (!openShift) throw Conflict('Cannot assign order to an off-shift waiter — they must clock in first');

  const updated = await prisma.order.update({ where: { id: orderId }, data: { createdByStaffId: waiterId } });

  await notifyUser(
    restaurantId,
    waiterId,
    'order_assignment',
    'New table assignment',
    `You have been assigned to serve Table ${order.table.tableNumber} (Order ${order.orderNumber})`,
    { tableNumber: order.table.tableNumber, orderNumber: order.orderNumber },
  );

  return updated;
}

const KITCHEN_ALLOWED_ITEM_STATUSES: OrderItemStatus[] = [OrderItemStatus.PREPARING, OrderItemStatus.READY];
const ITEM_UPDATE_BLOCKED_ORDER_STATUSES: OrderStatus[] = [OrderStatus.COMPLETED, OrderStatus.CANCELLED];

/**
 * Kitchen/waiter item-level status update, which then re-derives the parent
 * order's aggregate status. Two role-based rules ported verbatim from
 * legacy (app/controllers/api.php):
 *   - KITCHEN can only move an item to PREPARING/READY, never SERVED —
 *     serving is a front-of-house action.
 *   - WAITER can only touch beverage-category items directly (the "beverage
 *     exception" — drinks skip the kitchen prep queue entirely); food items
 *     stay kitchen/manager/admin territory. See order.state.ts::isBeverageCategory.
 */
export async function updateOrderItemStatus(restaurantId: string, orderItemId: string, newStatus: OrderItemStatus, staffId: string, staffRole: string) {
  const order = await prisma.$transaction(async (tx) => {
    const item = await tx.orderItem.findUnique({
      where: { id: orderItemId },
      include: { order: true, menuItem: { include: { category: { select: { name: true } } } } },
    });
    if (!item || item.order.restaurantId !== restaurantId) throw NotFound('Order item not found');

    if (ITEM_UPDATE_BLOCKED_ORDER_STATUSES.includes(item.order.status)) {
      throw Conflict(`Cannot update items in a ${item.order.status.toLowerCase()} order`);
    }

    if (staffRole === 'KITCHEN' && !KITCHEN_ALLOWED_ITEM_STATUSES.includes(newStatus)) {
      throw Forbidden('Kitchen can only mark items as preparing or ready');
    }

    if (staffRole === 'WAITER' && !isBeverageCategory(item.menuItem.category?.name)) {
      throw Forbidden('Waiters can only update status for beverage items');
    }

    await tx.orderItem.update({ where: { id: orderItemId }, data: { status: newStatus } });

    const siblingItems = await tx.orderItem.findMany({ where: { orderId: item.orderId } });
    const derivedStatus = deriveOrderStatusFromItems(siblingItems.map((i) => (i.id === orderItemId ? newStatus : i.status)));

    const updated = await tx.order.update({ where: { id: item.orderId }, data: { status: derivedStatus } });

    if (newStatus === OrderItemStatus.SERVED) {
      await tx.order.updateMany({ where: { id: item.orderId, servedById: null }, data: { servedById: staffId, servedAt: new Date() } });
    }

    return updated;
  });

  await publishRealtime({
    channel: 'customer',
    type: 'order_status',
    restaurantId,
    tableId: order.tableId,
  });
  await publishRealtime({ channel: 'staff', type: 'order_status', restaurantId });

  if (newStatus === OrderItemStatus.READY && order.customerPhone) {
    await notifyPhone(
      order.customerPhone,
      `Smart Restaurant: order ${order.orderNumber} is ready.`,
    );
  }

  return order;
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
  await assertSubscriptionActive(restaurantId);
  await assertFeature(restaurantId, 'basic_pos');
  const result = await prisma.$transaction(async (tx) => {
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

    let tableReleased = false;
    if (isFullyPaid) {
      await liabilityService.clearOnPayment(tx, orderId, receivedById, paymentMethod);
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

    return { ...updatedOrder, tableReleased, tableId: order.tableId };
  });

  await publishRealtime({ channel: 'customer', type: 'order_status', restaurantId, tableId: result.tableId });
  await publishRealtime({ channel: 'staff', type: 'order_status', restaurantId });
  return result;
}

async function releaseTableIfNoUnpaidOrders(
  tx: Prisma.TransactionClient,
  tableId: string,
  excludingOrderId: string,
): Promise<boolean> {
  const others = await tx.order.findMany({
    where: { tableId, id: { not: excludingOrderId } },
    select: { status: true, paymentStatus: true },
  });
  if (!shouldReleaseTableAfterSettlement(others)) return false;

  await tx.restaurantTable.update({ where: { id: tableId }, data: { status: 'AVAILABLE' } });
  await tx.deviceTableLock.updateMany({ where: { tableId, isActive: true }, data: { isActive: false } });
  return true;
}

/**
 * Delay-escalation check, ported from legacy `checkDelayedOrders`
 * (app/controllers/api.php): client-triggered on a polling interval rather
 * than a server-side cron job (no scheduler infra exists in either system),
 * so this runs whenever a staff client calls it. Two tiers, gated by the
 * order's own `escalationLevel` so each tier fires exactly once per order:
 *   - 5+ min past estimated prep time, level 0→1: reminder to the waiter
 *     who confirmed the order
 *   - 10+ min past estimated prep time, level 1→2: escalation to
 *     MANAGER/ADMIN, plus a second, urgent notice to the waiter
 * Estimated prep time is the slowest item on the order (matches legacy's
 * MAX(menu_items.preparation_time)), defaulting to 15 minutes if unset.
 */
export async function checkDelayedOrders(restaurantId: string) {
  const orders = await prisma.order.findMany({
    where: { restaurantId, status: { in: [OrderStatus.CONFIRMED, OrderStatus.PREPARING] }, confirmedAt: { not: null } },
    include: { items: { include: { menuItem: { select: { preparationTime: true } } } }, table: { select: { tableNumber: true } } },
  });

  const now = Date.now();
  const escalated: { orderId: string; orderNumber: string; type: string; delayMinutes: number }[] = [];

  for (const order of orders) {
    const estimatedMinutes = order.items.length > 0 ? Math.max(...order.items.map((i) => i.menuItem.preparationTime)) : DEFAULT_PREP_TIME_MINUTES;
    const minutesSinceConfirmed = (now - order.confirmedAt!.getTime()) / 60_000;
    const delayMinutes = minutesSinceConfirmed - estimatedMinutes;

    if (delayMinutes >= DELAY_REMINDER_MINUTES && order.escalationLevel === 0) {
      if (order.confirmedById) {
        await notifyUser(
          restaurantId,
          order.confirmedById,
          'order_delay_reminder',
          'Order delayed',
          `Order ${order.orderNumber} (Table ${order.table.tableNumber}) is delayed by ${Math.round(delayMinutes)}+ minutes. Please check the kitchen.`,
          { orderNumber: order.orderNumber, tableNumber: order.table.tableNumber, delayMinutes: Math.round(delayMinutes) },
        );
      }
      await prisma.order.update({ where: { id: order.id }, data: { escalationLevel: 1, firstReminderAt: new Date() } });
      escalated.push({ orderId: order.id, orderNumber: order.orderNumber, type: 'waiter_reminder', delayMinutes: Math.round(delayMinutes) });
    } else if (delayMinutes >= DELAY_ESCALATION_MINUTES && order.escalationLevel === 1) {
      await notifyRoles(
        restaurantId,
        ['MANAGER', 'ADMIN'],
        'order_delay_escalation',
        'Order delay escalation',
        `Order ${order.orderNumber} (Table ${order.table.tableNumber}) is delayed by ${Math.round(delayMinutes)}+ minutes!`,
        { orderNumber: order.orderNumber, tableNumber: order.table.tableNumber, delayMinutes: Math.round(delayMinutes), variant: 'manager' },
      );
      if (order.confirmedById) {
        await notifyUser(
          restaurantId,
          order.confirmedById,
          'order_delay_escalation',
          'Order delay escalation',
          `Order ${order.orderNumber} (Table ${order.table.tableNumber}) is now ${Math.round(delayMinutes)}+ minutes delayed. Management has been notified.`,
          { orderNumber: order.orderNumber, tableNumber: order.table.tableNumber, delayMinutes: Math.round(delayMinutes), variant: 'waiter' },
        );
      }
      await prisma.order.update({ where: { id: order.id }, data: { escalationLevel: 2, escalatedAt: new Date() } });
      escalated.push({ orderId: order.id, orderNumber: order.orderNumber, type: 'management_escalation', delayMinutes: Math.round(delayMinutes) });
    }
  }

  return { checkedOrders: orders.length, escalated };
}

export async function recordGuestHeardAbout(
  restaurantId: string,
  orderId: string,
  data: { skipped: boolean; channel: GuestHeardAboutChannel | null; rating: number | null; comment: string | null },
  tableId?: string,
) {
  const order = await prisma.order.findFirst({
    where: tableId ? customerOrderLookupWhere({ restaurantId, tableId }, orderId) : { id: orderId, restaurantId },
  });
  if (!order) throw NotFound('Order not found');
  if (order.paymentStatus !== PaymentStatus.PAID) {
    throw Conflict('Feedback is collected after the bill has been paid');
  }

  const result = await prisma.$transaction(async (tx) => {
    const existing = await tx.guestHeardAbout.findUnique({ where: { orderId } });
    const row =
      existing ??
      (await tx.guestHeardAbout.create({
        data: {
          restaurantId,
          orderId,
          skipped: data.skipped,
          channel: data.skipped ? null : data.channel,
          rating: data.skipped ? null : data.rating,
          comment: data.skipped ? null : data.comment,
        },
      }));

    const tableReleased = await releaseTableIfNoUnpaidOrders(tx, order.tableId, orderId);
    return { row, tableReleased, tableId: order.tableId };
  });

  if (result.tableReleased) {
    await publishRealtime({ channel: 'staff', type: 'table_status', restaurantId });
    await publishRealtime({ channel: 'customer', type: 'order_status', restaurantId, tableId: result.tableId });
  }

  return result.row;
}
