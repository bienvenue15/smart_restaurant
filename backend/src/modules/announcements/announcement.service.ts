import { AnnouncementPriority, AnnouncementType, AnnouncementAudience, StaffRole } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { NotFound } from '@/utils/httpError';
import { publishRealtime } from '@/services/realtime.service';

/**
 * `restaurantId: null` means every restaurant, which the realtime
 * visibility rules treat as a broadcast (see eventVisibleToStaff/Customer):
 * no restaurantId on the event reaches everyone rather than no one.
 */
async function pingAnnouncementChanged(restaurantId: string | null | undefined) {
  const scopedRestaurantId = restaurantId ?? undefined;
  await publishRealtime({ channel: 'staff', type: 'announcement_updated', restaurantId: scopedRestaurantId });
  await publishRealtime({ channel: 'customer', type: 'announcement_updated', restaurantId: scopedRestaurantId });
  await publishRealtime({ channel: 'platform', type: 'announcement_updated', restaurantId: scopedRestaurantId });
}

interface AnnouncementInput {
  title: string;
  message: string;
  type?: AnnouncementType;
  targetAudience?: AnnouncementAudience;
  priority?: AnnouncementPriority;
  restaurantId?: string | null;
  isActive?: boolean;
  isDismissible?: boolean;
  startDate?: Date | null;
  endDate?: Date | null;
}

export async function listAllAnnouncements() {
  return prisma.announcement.findMany({
    include: { restaurant: { select: { name: true } }, _count: { select: { dismissals: true } } },
    orderBy: { createdAt: 'desc' },
  });
}

export async function createAnnouncement(data: AnnouncementInput, createdById: string) {
  const created = await prisma.announcement.create({ data: { ...data, createdById } });
  await pingAnnouncementChanged(created.restaurantId);
  return created;
}

export async function updateAnnouncement(id: string, data: Partial<AnnouncementInput>) {
  const existing = await prisma.announcement.findUnique({ where: { id } });
  if (!existing) throw NotFound('Announcement not found');
  const updated = await prisma.announcement.update({ where: { id }, data });
  await pingAnnouncementChanged(updated.restaurantId);
  return updated;
}

export async function deleteAnnouncement(id: string) {
  const existing = await prisma.announcement.findUnique({ where: { id } });
  if (!existing) throw NotFound('Announcement not found');
  await prisma.announcement.delete({ where: { id } });
  await pingAnnouncementChanged(existing.restaurantId);
}

export async function toggleAnnouncement(id: string) {
  const existing = await prisma.announcement.findUnique({ where: { id } });
  if (!existing) throw NotFound('Announcement not found');
  const updated = await prisma.announcement.update({ where: { id }, data: { isActive: !existing.isActive } });
  await pingAnnouncementChanged(updated.restaurantId);
  return updated;
}

export async function getAnnouncementStats() {
  const all = await prisma.announcement.findMany();
  return {
    total: all.length,
    active: all.filter((a) => a.isActive).length,
    inactive: all.filter((a) => !a.isActive).length,
    forStaff: all.filter((a) => a.targetAudience === 'STAFF').length,
    forCustomers: all.filter((a) => a.targetAudience === 'CUSTOMERS').length,
    broadcast: all.filter((a) => a.restaurantId === null).length,
  };
}

const PRIORITY_RANK: Record<AnnouncementPriority, number> = { URGENT: 0, HIGH: 1, NORMAL: 2, LOW: 3 };

/** ADMINS-audience announcements are meant for restaurant leadership, not the whole floor staff. */
function audiencesFor(role: StaffRole): AnnouncementAudience[] {
  return role === 'ADMIN' || role === 'MANAGER' ? ['ALL', 'STAFF', 'ADMINS'] : ['ALL', 'STAFF'];
}

/**
 * Guest-menu announcements: ALL + CUSTOMERS, same restaurant/window rules
 * as staff. Dismissals are local to the device (customers have no StaffUser
 * row, so AnnouncementDismissal cannot apply).
 */
export async function listActiveForCustomer(restaurantId: string) {
  const now = new Date();
  const all = await prisma.announcement.findMany({
    where: {
      isActive: true,
      targetAudience: { in: ['ALL', 'CUSTOMERS'] },
      OR: [{ restaurantId: null }, { restaurantId }],
      AND: [{ OR: [{ startDate: null }, { startDate: { lte: now } }] }, { OR: [{ endDate: null }, { endDate: { gte: now } }] }],
    },
  });
  all.sort((a, b) => PRIORITY_RANK[a.priority] - PRIORITY_RANK[b.priority] || b.createdAt.getTime() - a.createdAt.getTime());
  return all;
}

/**
 * Active-announcement resolution, ported from legacy
 * `AnnouncementManager::getActiveAnnouncements`: active + audience match +
 * restaurant match (broadcast or this restaurant) + optional date window +
 * not already dismissed by this staff member.
 */
export async function listActiveForStaff(restaurantId: string, staffId: string, role: StaffRole) {
  const now = new Date();
  const all = await prisma.announcement.findMany({
    where: {
      isActive: true,
      targetAudience: { in: audiencesFor(role) },
      OR: [{ restaurantId: null }, { restaurantId }],
      AND: [{ OR: [{ startDate: null }, { startDate: { lte: now } }] }, { OR: [{ endDate: null }, { endDate: { gte: now } }] }],
      dismissals: { none: { userId: staffId } },
    },
  });
  all.sort((a, b) => PRIORITY_RANK[a.priority] - PRIORITY_RANK[b.priority] || b.createdAt.getTime() - a.createdAt.getTime());
  return all;
}

export async function dismissAnnouncement(announcementId: string, userId: string) {
  const announcement = await prisma.announcement.findUnique({ where: { id: announcementId } });
  if (!announcement) throw NotFound('Announcement not found');

  await prisma.announcementDismissal.upsert({
    where: { announcementId_userId: { announcementId, userId } },
    create: { announcementId, userId },
    update: {},
  });
}
