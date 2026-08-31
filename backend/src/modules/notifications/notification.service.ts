import { prisma } from '@/config/prisma';
import { NotFound } from '@/utils/httpError';
import { publishRealtime } from '@/services/realtime.service';

export async function listForStaff(staffId: string, includeRead: boolean) {
  return prisma.notification.findMany({
    where: { userId: staffId, ...(includeRead ? {} : { isRead: false }) },
    orderBy: { createdAt: 'desc' },
    take: 100,
  });
}

export async function markRead(staffId: string, notificationId: string) {
  const notification = await prisma.notification.findFirst({ where: { id: notificationId, userId: staffId } });
  if (!notification) throw NotFound('Notification not found');
  return prisma.notification.update({ where: { id: notificationId }, data: { isRead: true, readAt: new Date() } });
}

export async function markAllRead(staffId: string) {
  await prisma.notification.updateMany({ where: { userId: staffId, isRead: false }, data: { isRead: true, readAt: new Date() } });
}

export async function notifyRoles(restaurantId: string, roles: string[], type: string, title: string, message: string, data?: object) {
  const recipients = await prisma.staffUser.findMany({ where: { restaurantId, role: { in: roles as never[] }, isActive: true } });
  if (recipients.length === 0) return;

  await prisma.notification.createMany({
    data: recipients.map((r) => ({ restaurantId, userId: r.id, type, title, message, data })),
  });
  await publishRealtime({ channel: 'staff', type, restaurantId });
}

export async function notifyUser(restaurantId: string, userId: string, type: string, title: string, message: string, data?: object) {
  await prisma.notification.create({ data: { restaurantId, userId, type, title, message, data } });
  await publishRealtime({ channel: 'staff', type, restaurantId, userId });
}

/** Same as notifyRoles, but skips one staff member (the requester already got notifyUser). */
export async function notifyRolesExcept(
  restaurantId: string,
  roles: string[],
  exceptUserId: string,
  type: string,
  title: string,
  message: string,
  data?: object,
) {
  const recipients = await prisma.staffUser.findMany({
    where: { restaurantId, role: { in: roles as never[] }, isActive: true, id: { not: exceptUserId } },
  });
  if (recipients.length === 0) return;

  await prisma.notification.createMany({
    data: recipients.map((r) => ({ restaurantId, userId: r.id, type, title, message, data })),
  });
  await publishRealtime({ channel: 'staff', type, restaurantId });
}

/**
 * SUPER_ADMIN accounts have `restaurantId: null` (they're platform-level,
 * not tied to any one restaurant), so notifyRoles above — which filters
 * `WHERE restaurantId = ?` — can never match them. This queries across all
 * super admins instead, tagging the notification with the restaurant the
 * event concerns (e.g. a support ticket) purely for record-keeping; it does
 * not scope who receives it.
 */
export async function notifySuperAdmins(restaurantId: string, type: string, title: string, message: string, data?: object) {
  const recipients = await prisma.staffUser.findMany({ where: { role: 'SUPER_ADMIN', isActive: true } });
  if (recipients.length === 0) return;

  await prisma.notification.createMany({
    data: recipients.map((r) => ({ restaurantId, userId: r.id, type, title, message, data })),
  });
  await publishRealtime({ channel: 'platform', type, restaurantId });
}
