import { OrderStatus } from '@prisma/client';
import { prisma } from '@/config/prisma';

const REVENUE_RECOGNIZED_STATUSES: OrderStatus[] = [OrderStatus.COMPLETED];

/**
 * The real per-restaurant reporting surface. NOT the same thing as the
 * legacy Stats.php, which computed unscoped, cross-tenant PLATFORM-WIDE
 * metrics for the public marketing page (docs/CURRENT_SYSTEM_AUDIT.md §4 —
 * flagged there explicitly so this distinction isn't lost during
 * migration). Also consolidates the legacy's two duplicate/inconsistent
 * report code paths (`get_daily_report` family vs `staff_get_report`
 * wrapper, docs/CURRENT_SYSTEM_AUDIT.md §6) into one implementation.
 */
export async function getSalesReport(restaurantId: string, startDate: Date, endDate: Date) {
  const orders = await prisma.order.findMany({
    where: {
      restaurantId,
      createdAt: { gte: startDate, lte: endDate },
      status: { in: REVENUE_RECOGNIZED_STATUSES },
    },
    select: { totalAmount: true, paymentMethod: true, createdAt: true },
  });

  const totalRevenue = orders.reduce((sum, o) => sum + Number(o.totalAmount), 0);
  const orderCount = orders.length;
  const averageOrderValue = orderCount > 0 ? totalRevenue / orderCount : 0;

  const revenueByPaymentMethod: Record<string, number> = {};
  for (const order of orders) {
    const method = order.paymentMethod ?? 'unspecified';
    revenueByPaymentMethod[method] = (revenueByPaymentMethod[method] ?? 0) + Number(order.totalAmount);
  }

  const revenueByDay: Record<string, number> = {};
  for (const order of orders) {
    const day = order.createdAt.toISOString().slice(0, 10);
    revenueByDay[day] = (revenueByDay[day] ?? 0) + Number(order.totalAmount);
  }

  return { totalRevenue, orderCount, averageOrderValue, revenueByPaymentMethod, revenueByDay };
}

export async function getTopMenuItems(restaurantId: string, startDate: Date, endDate: Date, limit = 10) {
  const grouped = await prisma.orderItem.groupBy({
    by: ['menuItemId'],
    where: {
      order: { restaurantId, createdAt: { gte: startDate, lte: endDate }, status: { in: REVENUE_RECOGNIZED_STATUSES } },
    },
    _sum: { quantity: true, subtotal: true },
    orderBy: { _sum: { quantity: 'desc' } },
    take: limit,
  });

  const menuItems = await prisma.menuItem.findMany({
    where: { id: { in: grouped.map((g) => g.menuItemId) } },
    select: { id: true, name: true },
  });
  const nameById = new Map(menuItems.map((m) => [m.id, m.name]));

  return grouped.map((g) => ({
    menuItemId: g.menuItemId,
    name: nameById.get(g.menuItemId) ?? 'Unknown item',
    quantitySold: g._sum.quantity ?? 0,
    revenue: Number(g._sum.subtotal ?? 0),
  }));
}

export async function getDashboardStats(restaurantId: string) {
  const startOfToday = new Date();
  startOfToday.setHours(0, 0, 0, 0);

  const [todayOrders, todayRevenue, pendingOrders, activeTables, openCashSessions, pendingCalls, pendingAdjustments] = await Promise.all([
    prisma.order.count({ where: { restaurantId, createdAt: { gte: startOfToday } } }),
    prisma.order.aggregate({
      where: { restaurantId, createdAt: { gte: startOfToday }, status: { in: REVENUE_RECOGNIZED_STATUSES } },
      _sum: { totalAmount: true },
    }),
    prisma.order.count({ where: { restaurantId, status: 'PENDING' } }),
    prisma.restaurantTable.count({ where: { restaurantId, status: 'OCCUPIED' } }),
    prisma.cashSession.aggregate({ where: { restaurantId, status: 'OPEN' }, _sum: { cashInHand: true } }),
    prisma.waiterCall.count({ where: { restaurantId, status: 'PENDING' } }),
    prisma.orderAdjustment.count({ where: { order: { restaurantId }, status: 'PENDING' } }),
  ]);

  return {
    todayOrders,
    todayRevenue: Number(todayRevenue._sum.totalAmount ?? 0),
    pendingOrders,
    activeTables,
    cashInHand: Number(openCashSessions._sum.cashInHand ?? 0),
    pendingWaiterCalls: pendingCalls,
    pendingApprovals: pendingAdjustments,
  };
}
