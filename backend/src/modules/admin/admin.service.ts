import crypto from 'node:crypto';
import bcrypt from 'bcryptjs';
import { SubscriptionPlanName } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { BadRequest, NotFound } from '@/utils/httpError';
import { registerRestaurant } from '@/modules/restaurants/restaurant.service';
import { applyPlanToRestaurant, syncRestaurantsOnPlan } from '@/modules/subscriptions/subscription.service';

export async function listRestaurants() {
  return prisma.restaurant.findMany({ orderBy: { createdAt: 'desc' } });
}

export async function getRestaurant(restaurantId: string) {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) throw NotFound('Restaurant not found');
  return restaurant;
}

/**
 * Admin-driven onboarding. Reuses the same consolidated registration logic
 * as self-service signup (restaurant.service.ts) rather than duplicating
 * it a second time, unlike the legacy app (docs/CURRENT_SYSTEM_AUDIT.md
 * §4). Generates a temporary password and returns it ONCE in the API
 * response for the superadmin to relay manually — it is never logged or
 * emailed in plaintext (legacy did both, docs/SECURITY_AUDIT.md #11). Staff
 * can later use the signed password-reset link rather than a mailed raw password.
 */
export async function createRestaurant(data: {
  restaurantName: string;
  ownerName: string;
  email: string;
  phone?: string;
  tin?: string;
  address?: string;
  city?: string;
  subscriptionPlan?: SubscriptionPlanName;
}) {
  const temporaryPassword = crypto.randomBytes(9).toString('base64url');
  const result = await registerRestaurant({ ...data, password: temporaryPassword });

  if (data.subscriptionPlan && data.subscriptionPlan !== 'TRIAL') {
    await applyPlanToRestaurant(result.id, data.subscriptionPlan);
  }

  return { ...result, temporaryPassword };
}

export async function toggleRestaurantStatus(restaurantId: string, isActive: boolean) {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) throw NotFound('Restaurant not found');
  return prisma.restaurant.update({ where: { id: restaurantId }, data: { isActive } });
}

export async function extendSubscription(restaurantId: string, additionalDays: number) {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) throw NotFound('Restaurant not found');

  const base = restaurant.subscriptionEnd && restaurant.subscriptionEnd > new Date() ? restaurant.subscriptionEnd : new Date();
  const newEnd = new Date(base.getTime() + additionalDays * 24 * 60 * 60 * 1000);

  return prisma.restaurant.update({ where: { id: restaurantId }, data: { subscriptionEnd: newEnd, isActive: true } });
}

/** Soft delete only — mirrors the legacy's safer default (docs/CURRENT_SYSTEM_AUDIT.md §4 lists hard delete as a separate, rarer action). */
export async function deactivateRestaurant(restaurantId: string) {
  return toggleRestaurantStatus(restaurantId, false);
}

/**
 * Permanent tenant wipe. Restrict FKs (orders→tables, items→menu, cash→staff)
 * mean a naive `restaurant.delete` can fail, so children that block cascade
 * are removed first. Requires the caller to type the restaurant slug.
 */
export async function hardDeleteRestaurant(restaurantId: string, confirmSlug: string) {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) throw NotFound('Restaurant not found');
  if (confirmSlug.trim().toLowerCase() !== restaurant.slug.toLowerCase()) {
    throw BadRequest('Confirmation slug does not match this restaurant');
  }

  await prisma.$transaction(async (tx) => {
    await tx.cashTransaction.deleteMany({ where: { restaurantId } });
    await tx.cashSession.deleteMany({ where: { restaurantId } });
    await tx.orderItem.deleteMany({ where: { order: { restaurantId } } });
    await tx.payment.deleteMany({ where: { restaurantId } });
    await tx.orderAdjustment.deleteMany({ where: { order: { restaurantId } } });
    await tx.waiterLiability.deleteMany({ where: { restaurantId } });
    await tx.order.deleteMany({ where: { restaurantId } });
    await tx.restaurant.delete({ where: { id: restaurantId } });
  });

  return { id: restaurantId, slug: restaurant.slug, deleted: true };
}

export async function listPlatformUsers() {
  return prisma.staffUser.findMany({
    where: { role: { not: 'SUPER_ADMIN' } },
    select: {
      id: true,
      username: true,
      fullName: true,
      email: true,
      role: true,
      isActive: true,
      restaurant: { select: { id: true, name: true } },
    },
    orderBy: { createdAt: 'desc' },
  });
}

/**
 * The two platform-user operations that genuinely need a superadmin rather
 * than the owning restaurant's own ADMIN: locking an account nobody at that
 * restaurant can (or should) touch themselves, and account recovery when
 * the restaurant's ADMIN is the one who's locked out. Ordinary create/edit
 * is already covered by each restaurant's own staff CRUD.
 */
export async function togglePlatformUserStatus(userId: string, isActive: boolean) {
  const user = await prisma.staffUser.findFirst({ where: { id: userId, role: { not: 'SUPER_ADMIN' } } });
  if (!user) throw NotFound('Platform user not found');
  return prisma.staffUser.update({ where: { id: userId }, data: { isActive }, select: { id: true, fullName: true, isActive: true } });
}

export async function resetPlatformUserPassword(userId: string) {
  const user = await prisma.staffUser.findFirst({ where: { id: userId, role: { not: 'SUPER_ADMIN' } } });
  if (!user) throw NotFound('Platform user not found');

  const temporaryPassword = crypto.randomBytes(9).toString('base64url');
  const passwordHash = await bcrypt.hash(temporaryPassword, 10);
  await prisma.staffUser.update({ where: { id: userId }, data: { passwordHash } });

  return { username: user.username, temporaryPassword };
}

export async function getPlatformStats() {
  const today = new Date();
  const startOfToday = new Date();
  startOfToday.setHours(0, 0, 0, 0);
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

  const [totalRestaurants, activeRestaurants, totalOrders, todayOrders, monthRevenue, heardAbout] = await Promise.all([
    prisma.restaurant.count(),
    prisma.restaurant.count({ where: { isActive: true } }),
    prisma.order.count(),
    prisma.order.count({ where: { createdAt: { gte: startOfToday } } }),
    prisma.order.aggregate({ where: { createdAt: { gte: startOfMonth }, status: 'COMPLETED' }, _sum: { totalAmount: true } }),
    prisma.restaurant.groupBy({ by: ['heardAboutUs'], _count: { _all: true } }),
  ]);

  const restaurantsBySource: Record<string, number> = {};
  for (const row of heardAbout) {
    restaurantsBySource[row.heardAboutUs ?? 'UNKNOWN'] = row._count._all;
  }

  return {
    totalRestaurants,
    activeRestaurants,
    totalOrders,
    todayOrders,
    monthRevenue: Number(monthRevenue._sum.totalAmount ?? 0),
    restaurantsBySource,
  };
}

/**
 * Cross-tenant version of activityLog.service.ts::getActivityLog — the
 * per-restaurant activity log merges StaffActivityLog + AuditTrail scoped
 * to one restaurant; this does the same merge with no restaurant filter
 * (superadmin can see every tenant), tagging each row with which restaurant
 * it came from. Legacy had no equivalent platform-wide view at all.
 */
export async function getPlatformAuditLog(filters: { restaurantId?: string; search?: string }, limit = 200) {
  const restaurantWhere = filters.restaurantId ? { restaurantId: filters.restaurantId } : {};

  const [activity, audit] = await Promise.all([
    prisma.staffActivityLog.findMany({
      where: { staff: { ...restaurantWhere } },
      include: { staff: { select: { fullName: true, restaurant: { select: { id: true, name: true } } } } },
      orderBy: { createdAt: 'desc' },
      take: limit,
    }),
    prisma.auditTrail.findMany({
      where: { ...restaurantWhere },
      include: { staff: { select: { fullName: true } }, restaurant: { select: { id: true, name: true } } },
      orderBy: { createdAt: 'desc' },
      take: limit,
    }),
  ]);

  const merged = [
    ...activity.map((a) => ({
      source: 'activity' as const,
      id: a.id,
      restaurantId: a.staff.restaurant?.id ?? null,
      restaurantName: a.staff.restaurant?.name ?? null,
      staffName: a.staff.fullName,
      action: a.action,
      description: a.description,
      createdAt: a.createdAt,
    })),
    ...audit.map((a) => ({
      source: 'audit' as const,
      id: a.id,
      restaurantId: a.restaurant?.id ?? null,
      restaurantName: a.restaurant?.name ?? null,
      staffName: a.staff.fullName,
      action: a.actionType,
      description: a.reason,
      createdAt: a.createdAt,
    })),
  ].sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime());

  const filtered = filters.search
    ? merged.filter((row) => {
        const needle = filters.search!.toLowerCase();
        return row.staffName.toLowerCase().includes(needle) || row.action.toLowerCase().includes(needle) || (row.restaurantName?.toLowerCase().includes(needle) ?? false);
      })
    : merged;

  return filtered.slice(0, limit);
}

/**
 * The trend/per-restaurant breakdown the checklist flagged as missing from
 * the basic aggregate `getPlatformStats` above — last 30 days, COMPLETED
 * orders only (same revenue-recognition rule as the per-restaurant reports
 * in reports.service.ts), computed in JS since it's a cross-tenant
 * aggregation with no restaurantId to group by ahead of time.
 */
export async function getPlatformAnalytics() {
  const thirtyDaysAgo = new Date();
  thirtyDaysAgo.setHours(0, 0, 0, 0);
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 29);

  const orders = await prisma.order.findMany({
    where: { createdAt: { gte: thirtyDaysAgo }, status: 'COMPLETED' },
    select: { totalAmount: true, createdAt: true, restaurantId: true, restaurant: { select: { name: true } } },
  });

  const revenueByDay: Record<string, number> = {};
  const byRestaurant = new Map<string, { restaurantId: string; name: string; orders: number; revenue: number }>();

  for (const order of orders) {
    const day = order.createdAt.toISOString().slice(0, 10);
    revenueByDay[day] = (revenueByDay[day] ?? 0) + Number(order.totalAmount);

    const existing = byRestaurant.get(order.restaurantId);
    if (existing) {
      existing.orders += 1;
      existing.revenue += Number(order.totalAmount);
    } else {
      byRestaurant.set(order.restaurantId, { restaurantId: order.restaurantId, name: order.restaurant.name, orders: 1, revenue: Number(order.totalAmount) });
    }
  }

  return {
    revenueByDay,
    byRestaurant: Array.from(byRestaurant.values()).sort((a, b) => b.revenue - a.revenue),
  };
}

export async function listSubscriptionPlans() {
  return prisma.subscriptionPlan.findMany({ orderBy: { priceMonthly: 'asc' } });
}

export async function updateSubscriptionPlan(planName: SubscriptionPlanName, data: Partial<Record<string, unknown>>) {
  const plan = await prisma.subscriptionPlan.findUnique({ where: { planName } });
  if (!plan) throw NotFound('Subscription plan not found');
  const updated = await prisma.subscriptionPlan.update({ where: { planName }, data });
  if (data.maxTables !== undefined || data.maxUsers !== undefined) {
    await syncRestaurantsOnPlan(planName, { maxTables: updated.maxTables, maxUsers: updated.maxUsers });
  }
  return updated;
}

export async function assignRestaurantPlan(restaurantId: string, planName: SubscriptionPlanName) {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) throw NotFound('Restaurant not found');
  return applyPlanToRestaurant(restaurantId, planName);
}
