import { prisma } from '@/config/prisma';
import { NotFound } from '@/utils/httpError';
import { LIMIT_CHECKERS } from '@/modules/subscriptions/subscription.service';
import { notifyRoles } from '@/modules/notifications/notification.service';
import { publishRealtime } from '@/services/realtime.service';

/**
 * Plain "menu changed, refetch" ping — separate from notifyRoles, which
 * writes a human-readable notification-bell row. Menu edits are frequent
 * and not something a manager needs to read as a notification, but both
 * the staff menu editor and the customer-facing menu should still update
 * live without a reload.
 */
async function pingMenuChanged(restaurantId: string) {
  await publishRealtime({ channel: 'staff', type: 'menu_updated', restaurantId });
  await publishRealtime({ channel: 'customer', type: 'menu_updated', restaurantId });
}

export async function getFullMenu(restaurantId: string) {
  return prisma.menuCategory.findMany({
    where: { restaurantId, isActive: true },
    orderBy: [{ displayOrder: 'asc' }, { name: 'asc' }],
    include: {
      items: {
        orderBy: [{ displayOrder: 'asc' }, { name: 'asc' }],
      },
    },
  });
}

export async function createCategory(restaurantId: string, data: { name: string; description?: string; displayOrder?: number }) {
  const category = await prisma.menuCategory.create({ data: { ...data, restaurantId } });
  await pingMenuChanged(restaurantId);
  return category;
}

export async function updateCategory(restaurantId: string, categoryId: string, data: Partial<{ name: string; description: string; displayOrder: number; isActive: boolean }>) {
  const category = await prisma.menuCategory.findFirst({ where: { id: categoryId, restaurantId } });
  if (!category) throw NotFound('Category not found');
  const updated = await prisma.menuCategory.update({ where: { id: categoryId }, data });
  await pingMenuChanged(restaurantId);
  return updated;
}

export async function deleteCategory(restaurantId: string, categoryId: string) {
  const category = await prisma.menuCategory.findFirst({ where: { id: categoryId, restaurantId } });
  if (!category) throw NotFound('Category not found');
  await prisma.menuCategory.delete({ where: { id: categoryId } });
  await pingMenuChanged(restaurantId);
}

export async function createMenuItem(
  restaurantId: string,
  data: {
    categoryId: string;
    name: string;
    description?: string;
    price: number;
    preparationTime?: number;
    isAvailable?: boolean;
    isSpecial?: boolean;
    dietaryInfo?: string;
  },
) {
  const category = await prisma.menuCategory.findFirst({ where: { id: data.categoryId, restaurantId } });
  if (!category) throw NotFound('Category not found');
  await LIMIT_CHECKERS.menuItems(restaurantId);
  const item = await prisma.menuItem.create({ data: { ...data, restaurantId } });
  await pingMenuChanged(restaurantId);
  return item;
}

export async function updateMenuItem(restaurantId: string, itemId: string, data: Partial<Record<string, unknown>>) {
  const item = await prisma.menuItem.findFirst({ where: { id: itemId, restaurantId } });
  if (!item) throw NotFound('Menu item not found');
  const updated = await prisma.menuItem.update({ where: { id: itemId }, data });
  await pingMenuChanged(restaurantId);
  return updated;
}

export async function setImageUrl(restaurantId: string, itemId: string, imageUrl: string) {
  const item = await prisma.menuItem.findFirst({ where: { id: itemId, restaurantId } });
  if (!item) throw NotFound('Menu item not found');
  const updated = await prisma.menuItem.update({ where: { id: itemId }, data: { imageUrl } });
  await pingMenuChanged(restaurantId);
  return updated;
}

export async function setAvailability(restaurantId: string, itemId: string, isAvailable: boolean) {
  const item = await prisma.menuItem.findFirst({ where: { id: itemId, restaurantId } });
  if (!item) throw NotFound('Menu item not found');
  const updated = await prisma.menuItem.update({ where: { id: itemId }, data: { isAvailable } });

  await notifyRoles(
    restaurantId,
    ['MANAGER', 'ADMIN'],
    'menu_availability',
    'Menu availability changed',
    `${item.name} is now ${isAvailable ? 'available' : 'unavailable'}`,
    { itemName: item.name, isAvailable },
  );
  await publishRealtime({ channel: 'customer', type: 'menu_updated', restaurantId });

  return updated;
}
