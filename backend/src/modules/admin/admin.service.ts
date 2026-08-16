import crypto from 'node:crypto';
import { SubscriptionPlanName } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { NotFound } from '@/utils/httpError';
import { registerRestaurant } from '@/modules/restaurants/restaurant.service';

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
 * emailed in plaintext (legacy did both, docs/SECURITY_AUDIT.md #11). A
 * proper invite-link / forced-password-reset flow is a follow-up
 * improvement (docs/FEATURE_PARITY_CHECKLIST.md), not yet built.
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
    await prisma.restaurant.update({ where: { id: result.id }, data: { subscriptionPlan: data.subscriptionPlan } });
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

export async function getPlatformStats() {
  const today = new Date();
  const startOfToday = new Date();
  startOfToday.setHours(0, 0, 0, 0);
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

  const [totalRestaurants, activeRestaurants, totalOrders, todayOrders, monthRevenue] = await Promise.all([
    prisma.restaurant.count(),
    prisma.restaurant.count({ where: { isActive: true } }),
    prisma.order.count(),
    prisma.order.count({ where: { createdAt: { gte: startOfToday } } }),
    prisma.order.aggregate({ where: { createdAt: { gte: startOfMonth }, status: 'COMPLETED' }, _sum: { totalAmount: true } }),
  ]);

  return {
    totalRestaurants,
    activeRestaurants,
    totalOrders,
    todayOrders,
    monthRevenue: Number(monthRevenue._sum.totalAmount ?? 0),
  };
}

export async function listSubscriptionPlans() {
  return prisma.subscriptionPlan.findMany({ orderBy: { priceMonthly: 'asc' } });
}

export async function updateSubscriptionPlan(planName: SubscriptionPlanName, data: Partial<Record<string, unknown>>) {
  const plan = await prisma.subscriptionPlan.findUnique({ where: { planName } });
  if (!plan) throw NotFound('Subscription plan not found');
  return prisma.subscriptionPlan.update({ where: { planName }, data });
}
