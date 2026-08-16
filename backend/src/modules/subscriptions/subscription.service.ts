import { prisma } from '@/config/prisma';
import { Forbidden, NotFound } from '@/utils/httpError';

/**
 * Live plan-limit enforcement, ported from the legacy SubscriptionManager
 * (docs/CURRENT_SYSTEM_AUDIT.md §4 — legacy checks table/user/menu-item/
 * monthly-order counts against the restaurant's plan on every relevant
 * create action, not just at signup). No payment gateway exists in the
 * legacy system and none is built here either — plan changes are
 * superadmin-driven (see modules/admin), matching legacy business reality
 * rather than assuming a gateway that was never confirmed as required
 * (docs/TARGET_ARCHITECTURE.md "Open questions for the business").
 */
export async function isSubscriptionActive(restaurantId: string): Promise<boolean> {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) return false;
  const today = new Date();
  return restaurant.isActive && (!restaurant.subscriptionEnd || restaurant.subscriptionEnd >= today);
}

export async function getPlanLimits(restaurantId: string) {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) throw NotFound('Restaurant not found');

  const plan = await prisma.subscriptionPlan.findUnique({ where: { planName: restaurant.subscriptionPlan } });
  // Falls back to the restaurant's own maxTables/maxUsers if the plan row is
  // somehow missing — mirrors the legacy fallback-to-trial-defaults behavior.
  return {
    maxTables: plan?.maxTables ?? restaurant.maxTables,
    maxUsers: plan?.maxUsers ?? restaurant.maxUsers,
    maxMenuItems: plan?.maxMenuItems ?? 50,
    maxOrdersPerMonth: plan?.maxOrdersPerMonth ?? 200,
  };
}

async function checkTableLimit(restaurantId: string): Promise<void> {
  const [limits, count] = await Promise.all([
    getPlanLimits(restaurantId),
    prisma.restaurantTable.count({ where: { restaurantId } }),
  ]);
  if (count >= limits.maxTables) throw Forbidden(`Table limit reached for your plan (${limits.maxTables})`);
}

async function checkUserLimit(restaurantId: string): Promise<void> {
  const [limits, count] = await Promise.all([
    getPlanLimits(restaurantId),
    prisma.staffUser.count({ where: { restaurantId, isActive: true } }),
  ]);
  if (count >= limits.maxUsers) throw Forbidden(`Staff seat limit reached for your plan (${limits.maxUsers})`);
}

async function checkMenuItemLimit(restaurantId: string): Promise<void> {
  const [limits, count] = await Promise.all([
    getPlanLimits(restaurantId),
    prisma.menuItem.count({ where: { restaurantId } }),
  ]);
  if (count >= limits.maxMenuItems) throw Forbidden(`Menu item limit reached for your plan (${limits.maxMenuItems})`);
}

async function checkMonthlyOrderLimit(restaurantId: string): Promise<void> {
  const startOfMonth = new Date();
  startOfMonth.setDate(1);
  startOfMonth.setHours(0, 0, 0, 0);

  const [limits, count] = await Promise.all([
    getPlanLimits(restaurantId),
    prisma.order.count({ where: { restaurantId, createdAt: { gte: startOfMonth } } }),
  ]);
  if (count >= limits.maxOrdersPerMonth) throw Forbidden(`Monthly order limit reached for your plan (${limits.maxOrdersPerMonth})`);
}

export const LIMIT_CHECKERS = {
  tables: checkTableLimit,
  users: checkUserLimit,
  menuItems: checkMenuItemLimit,
  ordersPerMonth: checkMonthlyOrderLimit,
};
