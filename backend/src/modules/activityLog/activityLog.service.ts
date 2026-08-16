import { prisma } from '@/config/prisma';

/**
 * Merges staff_activity_log (logins/clock-in-out) and audit_trail
 * (business-action history) into one restaurant-scoped feed, matching the
 * legacy's `activity_log` page which did the same merge
 * (docs/CURRENT_SYSTEM_AUDIT.md §4).
 */
export async function getActivityLog(restaurantId: string, limit = 100) {
  const [activity, audit] = await Promise.all([
    prisma.staffActivityLog.findMany({
      where: { staff: { restaurantId } },
      include: { staff: { select: { fullName: true } } },
      orderBy: { createdAt: 'desc' },
      take: limit,
    }),
    prisma.auditTrail.findMany({
      where: { restaurantId },
      include: { staff: { select: { fullName: true } } },
      orderBy: { createdAt: 'desc' },
      take: limit,
    }),
  ]);

  const merged = [
    ...activity.map((a) => ({
      source: 'activity' as const,
      id: a.id,
      staffName: a.staff.fullName,
      action: a.action,
      description: a.description,
      createdAt: a.createdAt,
    })),
    ...audit.map((a) => ({
      source: 'audit' as const,
      id: a.id,
      staffName: a.staff.fullName,
      action: a.actionType,
      description: a.reason,
      createdAt: a.createdAt,
    })),
  ];

  return merged.sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime()).slice(0, limit);
}
