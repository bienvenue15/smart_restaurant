import { SubscriptionPlanName } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { Forbidden, NotFound } from '@/utils/httpError';

/**
 * Feature codes that map to a real product surface. Admin can toggle these
 * on a plan; the API and staff UI both honour them. Other strings in
 * `SubscriptionPlan.features` (inventory, multi_location, …) stay as labels
 * only — those modules do not exist yet (docs/GO_LIVE.md §9).
 */
export const ENFORCEABLE_FEATURES = ['basic_pos', 'qr_ordering', 'kitchen_display', 'analytics'] as const;
export type EnforceableFeature = (typeof ENFORCEABLE_FEATURES)[number];

export interface PlanLimits {
  maxTables: number;
  maxUsers: number;
  maxMenuItems: number;
  maxOrdersPerMonth: number;
}

export interface PlanUsage {
  tables: number;
  users: number;
  menuItems: number;
  ordersThisMonth: number;
}

export interface PlanEntitlements {
  planName: string;
  displayName: string;
  features: string[];
  limits: PlanLimits;
  usage?: PlanUsage;
  subscriptionActive: boolean;
  subscriptionEnd: Date | null;
}

export function featuresList(json: unknown): string[] {
  if (!Array.isArray(json)) return [];
  return json.filter((value): value is string => typeof value === 'string' && value.trim().length > 0);
}

export function planHasFeature(features: string[], code: string): boolean {
  return features.includes(code);
}

function startOfMonth(now = new Date()): Date {
  return new Date(now.getFullYear(), now.getMonth(), 1);
}

function isRestaurantSubscriptionActive(restaurant: { isActive: boolean; subscriptionEnd: Date | null }): boolean {
  if (!restaurant.isActive) return false;
  if (!restaurant.subscriptionEnd) return true;
  return restaurant.subscriptionEnd >= new Date();
}

export async function isSubscriptionActive(restaurantId: string): Promise<boolean> {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) return false;
  return isRestaurantSubscriptionActive(restaurant);
}

export async function assertSubscriptionActive(restaurantId: string): Promise<void> {
  if (!(await isSubscriptionActive(restaurantId))) {
    throw Forbidden('This restaurant’s subscription is inactive or expired');
  }
}

export async function getPlanLimits(restaurantId: string): Promise<PlanLimits> {
  const entitlements = await getEntitlements(restaurantId);
  return entitlements.limits;
}

export async function getEntitlements(restaurantId: string, options?: { includeUsage?: boolean }): Promise<PlanEntitlements> {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) throw NotFound('Restaurant not found');

  const plan = await prisma.subscriptionPlan.findUnique({ where: { planName: restaurant.subscriptionPlan } });
  const limits: PlanLimits = {
    maxTables: plan?.maxTables ?? restaurant.maxTables,
    maxUsers: plan?.maxUsers ?? restaurant.maxUsers,
    maxMenuItems: plan?.maxMenuItems ?? 50,
    maxOrdersPerMonth: plan?.maxOrdersPerMonth ?? 200,
  };

  const entitlements: PlanEntitlements = {
    planName: restaurant.subscriptionPlan,
    displayName: plan?.displayName ?? restaurant.subscriptionPlan,
    features: featuresList(plan?.features),
    limits,
    subscriptionActive: isRestaurantSubscriptionActive(restaurant),
    subscriptionEnd: restaurant.subscriptionEnd,
  };

  if (options?.includeUsage) {
    const [tables, users, menuItems, ordersThisMonth] = await Promise.all([
      prisma.restaurantTable.count({ where: { restaurantId } }),
      prisma.staffUser.count({ where: { restaurantId, isActive: true } }),
      prisma.menuItem.count({ where: { restaurantId } }),
      prisma.order.count({ where: { restaurantId, createdAt: { gte: startOfMonth() } } }),
    ]);
    entitlements.usage = { tables, users, menuItems, ordersThisMonth };
  }

  return entitlements;
}

export async function hasFeature(restaurantId: string, code: EnforceableFeature | string): Promise<boolean> {
  const entitlements = await getEntitlements(restaurantId);
  return entitlements.subscriptionActive && planHasFeature(entitlements.features, code);
}

export async function assertFeature(restaurantId: string, code: EnforceableFeature | string): Promise<void> {
  await assertSubscriptionActive(restaurantId);
  const entitlements = await getEntitlements(restaurantId);
  if (!planHasFeature(entitlements.features, code)) {
    throw Forbidden(`Your plan does not include ${code.replace(/_/g, ' ')}`);
  }
}

export function restaurantFieldsFromPlan(plan: {
  maxTables: number;
  maxUsers: number;
  durationDays: number;
}): { maxTables: number; maxUsers: number; subscriptionStart: Date; subscriptionEnd: Date } {
  const subscriptionStart = new Date();
  return {
    maxTables: plan.maxTables,
    maxUsers: plan.maxUsers,
    subscriptionStart,
    subscriptionEnd: new Date(subscriptionStart.getTime() + plan.durationDays * 24 * 60 * 60 * 1000),
  };
}

export async function applyPlanToRestaurant(
  restaurantId: string,
  planName: SubscriptionPlanName,
  options?: { resetPeriod?: boolean },
) {
  const plan = await prisma.subscriptionPlan.findUnique({ where: { planName } });
  if (!plan) throw NotFound('Subscription plan not found');
  if (!plan.isActive) throw Forbidden('That subscription plan is not available');

  const data: {
    subscriptionPlan: SubscriptionPlanName;
    maxTables: number;
    maxUsers: number;
    subscriptionStart?: Date;
    subscriptionEnd?: Date;
    isActive: boolean;
  } = {
    subscriptionPlan: planName,
    maxTables: plan.maxTables,
    maxUsers: plan.maxUsers,
    isActive: true,
  };

  if (options?.resetPeriod !== false) {
    const period = restaurantFieldsFromPlan(plan);
    data.subscriptionStart = period.subscriptionStart;
    data.subscriptionEnd = period.subscriptionEnd;
  }

  return prisma.restaurant.update({ where: { id: restaurantId }, data });
}

export async function syncRestaurantsOnPlan(planName: SubscriptionPlanName, limits: { maxTables: number; maxUsers: number }) {
  await prisma.restaurant.updateMany({
    where: { subscriptionPlan: planName },
    data: { maxTables: limits.maxTables, maxUsers: limits.maxUsers },
  });
}

async function checkTableLimit(restaurantId: string): Promise<void> {
  await assertSubscriptionActive(restaurantId);
  await assertFeature(restaurantId, 'basic_pos');
  const [limits, count] = await Promise.all([
    getPlanLimits(restaurantId),
    prisma.restaurantTable.count({ where: { restaurantId } }),
  ]);
  if (count >= limits.maxTables) throw Forbidden(`Table limit reached for your plan (${limits.maxTables})`);
}

async function checkUserLimit(restaurantId: string): Promise<void> {
  await assertSubscriptionActive(restaurantId);
  const [limits, count] = await Promise.all([
    getPlanLimits(restaurantId),
    prisma.staffUser.count({ where: { restaurantId, isActive: true } }),
  ]);
  if (count >= limits.maxUsers) throw Forbidden(`Staff seat limit reached for your plan (${limits.maxUsers})`);
}

async function checkMenuItemLimit(restaurantId: string): Promise<void> {
  await assertSubscriptionActive(restaurantId);
  const [limits, count] = await Promise.all([
    getPlanLimits(restaurantId),
    prisma.menuItem.count({ where: { restaurantId } }),
  ]);
  if (count >= limits.maxMenuItems) throw Forbidden(`Menu item limit reached for your plan (${limits.maxMenuItems})`);
}

async function checkMonthlyOrderLimit(restaurantId: string): Promise<void> {
  await assertSubscriptionActive(restaurantId);
  await assertFeature(restaurantId, 'qr_ordering');
  const [limits, count] = await Promise.all([
    getPlanLimits(restaurantId),
    prisma.order.count({ where: { restaurantId, createdAt: { gte: startOfMonth() } } }),
  ]);
  if (count >= limits.maxOrdersPerMonth) throw Forbidden(`Monthly order limit reached for your plan (${limits.maxOrdersPerMonth})`);
}

export const LIMIT_CHECKERS = {
  tables: checkTableLimit,
  users: checkUserLimit,
  menuItems: checkMenuItemLimit,
  ordersPerMonth: checkMonthlyOrderLimit,
};
