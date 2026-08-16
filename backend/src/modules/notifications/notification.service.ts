import { prisma } from '@/config/prisma';
import { NotFound } from '@/utils/httpError';

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
}
