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

  const guestWhere = {
    restaurantId,
    skipped: false,
    createdAt: { gte: startDate, lte: endDate },
  };

  const [guestHeardAbout, ratingAgg, guestCommentRows] = await Promise.all([
    prisma.guestHeardAbout.groupBy({
      by: ['channel'],
      where: { ...guestWhere, channel: { not: null } },
      _count: { _all: true },
    }),
    prisma.guestHeardAbout.aggregate({
      where: { ...guestWhere, rating: { not: null } },
      _avg: { rating: true },
      _count: { rating: true },
    }),
    prisma.guestHeardAbout.findMany({
      where: { ...guestWhere, comment: { not: null } },
      select: {
        rating: true,
        comment: true,
        createdAt: true,
        order: { select: { orderNumber: true, table: { select: { tableNumber: true } } } },
      },
      orderBy: { createdAt: 'desc' },
      take: 20,
    }),
  ]);

  const guestsBySource: Record<string, number> = {};
  for (const row of guestHeardAbout) {
    if (row.channel) guestsBySource[row.channel] = row._count._all;
  }

  const guestComments = guestCommentRows
    .filter((row): row is typeof row & { comment: string } => Boolean(row.comment))
    .map((row) => ({
      rating: row.rating,
      comment: row.comment,
      createdAt: row.createdAt,
      orderNumber: row.order.orderNumber,
      tableNumber: row.order.table.tableNumber,
    }));

  return {
    totalRevenue,
    orderCount,
    averageOrderValue,
    revenueByPaymentMethod,
    revenueByDay,
    guestsBySource,
    averageRating: ratingAgg._count.rating > 0 ? Number(ratingAgg._avg.rating) : null,
    ratingCount: ratingAgg._count.rating,
    guestComments,
  };
}

export type ProfitLossPeriod = 'daily' | 'weekly' | 'monthly';

function periodRange(period: ProfitLossPeriod): { startDate: Date; endDate: Date } {
  const endDate = new Date();
  const startDate = new Date();
  if (period === 'daily') {
    startDate.setHours(0, 0, 0, 0);
  } else if (period === 'weekly') {
    const day = startDate.getDay(); // 0=Sun..6=Sat
    const diffToMonday = day === 0 ? 6 : day - 1;
    startDate.setDate(startDate.getDate() - diffToMonday);
    startDate.setHours(0, 0, 0, 0);
  } else {
    startDate.setDate(1);
    startDate.setHours(0, 0, 0, 0);
  }
  return { startDate, endDate };
}

/**
 * Owner-only profit & loss snapshot (`view_financials` permission — ADMIN
 * only among restaurant-scoped roles, see permissions.ts). There's no
 * ingredient/COGS cost model in this system, so "cost" here is every
 * concrete outflow the data actually captures: cash withdrawals/expenses,
 * approved refunds, and liabilities that ended in LOSS or were WAIVED
 * (money legitimately earned but never collected). Revenue uses the same
 * COMPLETED-order recognition rule as the rest of this module.
 */
export async function getProfitLoss(restaurantId: string, period: ProfitLossPeriod) {
  const { startDate, endDate } = periodRange(period);
  const dateWhere = { gte: startDate, lte: endDate };

  const [revenueAgg, cashOut, refunds, liabilityLosses] = await Promise.all([
    prisma.order.aggregate({
      where: { restaurantId, createdAt: dateWhere, status: { in: REVENUE_RECOGNIZED_STATUSES } },
      _sum: { totalAmount: true },
    }),
    prisma.cashTransaction.groupBy({
      by: ['transactionType'],
      where: { restaurantId, createdAt: dateWhere, transactionType: { in: ['EXPENSE', 'WITHDRAWAL'] } },
      _sum: { amount: true },
    }),
    prisma.orderAdjustment.aggregate({
      where: { order: { restaurantId }, adjustmentType: 'REFUND', status: 'APPROVED', createdAt: dateWhere },
      _sum: { amount: true },
    }),
    prisma.waiterLiability.groupBy({
      by: ['status'],
      where: { restaurantId, status: { in: ['LOSS', 'WAIVED'] }, liabilityCreatedAt: dateWhere },
      _sum: { orderAmount: true },
    }),
  ]);

  const revenue = Number(revenueAgg._sum.totalAmount ?? 0);
  const expenses = Number(cashOut.find((c) => c.transactionType === 'EXPENSE')?._sum.amount ?? 0);
  const withdrawals = Number(cashOut.find((c) => c.transactionType === 'WITHDRAWAL')?._sum.amount ?? 0);
  const refundTotal = Number(refunds._sum.amount ?? 0);
  const liabilityLoss = Number(liabilityLosses.find((l) => l.status === 'LOSS')?._sum.orderAmount ?? 0);
  const liabilityWaived = Number(liabilityLosses.find((l) => l.status === 'WAIVED')?._sum.orderAmount ?? 0);

  const costs = expenses + withdrawals + refundTotal + liabilityLoss + liabilityWaived;
  const net = revenue - costs;
  const marginPct = revenue > 0 ? Number(((net / revenue) * 100).toFixed(2)) : 0;

  return {
    period,
    startDate,
    endDate,
    revenue,
    costs,
    costBreakdown: { expenses, withdrawals, refunds: refundTotal, liabilityLoss, liabilityWaived },
    net,
    marginPct,
    status: net > 0 ? 'PROFIT' : net < 0 ? 'LOSS' : 'BREAK_EVEN',
  };
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

  const [
    todayOrders,
    todayRevenue,
    pendingOrders,
    activeTables,
    openCashSessions,
    pendingCalls,
    pendingAdjustments,
    pendingCashCloses,
    activeLiabilities,
    restaurant,
    completedOrders,
  ] = await Promise.all([
    prisma.order.count({ where: { restaurantId, createdAt: { gte: startOfToday } } }),
    prisma.order.aggregate({
      where: { restaurantId, createdAt: { gte: startOfToday }, status: { in: REVENUE_RECOGNIZED_STATUSES } },
      _sum: { totalAmount: true },
    }),
    prisma.order.count({ where: { restaurantId, status: 'PENDING' } }),
    prisma.restaurantTable.count({ where: { restaurantId, status: 'OCCUPIED' } }),
    prisma.cashSession.aggregate({ where: { restaurantId, status: { in: ['OPEN', 'AUDITING'] } }, _sum: { cashInHand: true } }),
    prisma.waiterCall.count({ where: { restaurantId, status: 'PENDING' } }),
    prisma.orderAdjustment.count({ where: { order: { restaurantId }, status: 'PENDING' } }),
    prisma.cashSession.count({ where: { restaurantId, status: 'AUDITING' } }),
    prisma.waiterLiability.count({ where: { restaurantId, status: 'ACTIVE' } }),
    prisma.restaurant.findUnique({
      where: { id: restaurantId },
      select: { heardAboutUs: true, heardAboutSkipped: true },
    }),
    prisma.order.count({ where: { restaurantId, status: 'COMPLETED' } }),
  ]);

  return {
    todayOrders,
    todayRevenue: Number(todayRevenue._sum.totalAmount ?? 0),
    pendingOrders,
    activeTables,
    cashInHand: Number(openCashSessions._sum.cashInHand ?? 0),
    pendingWaiterCalls: pendingCalls,
    pendingApprovals: pendingAdjustments + pendingCashCloses,
    pendingLiabilities: activeLiabilities,
    askHowYouFoundUs: !restaurant?.heardAboutUs && !restaurant?.heardAboutSkipped && completedOrders > 0,
  };
}
