import { prisma } from '@/config/prisma';
import { NotFound } from '@/utils/httpError';
import { LIMIT_CHECKERS } from '@/modules/subscriptions/subscription.service';

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
  return prisma.menuCategory.create({ data: { ...data, restaurantId } });
}

export async function updateCategory(restaurantId: string, categoryId: string, data: Partial<{ name: string; description: string; displayOrder: number; isActive: boolean }>) {
  const category = await prisma.menuCategory.findFirst({ where: { id: categoryId, restaurantId } });
  if (!category) throw NotFound('Category not found');
  return prisma.menuCategory.update({ where: { id: categoryId }, data });
}

export async function deleteCategory(restaurantId: string, categoryId: string) {
  const category = await prisma.menuCategory.findFirst({ where: { id: categoryId, restaurantId } });
  if (!category) throw NotFound('Category not found');
  await prisma.menuCategory.delete({ where: { id: categoryId } });
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
  return prisma.menuItem.create({ data: { ...data, restaurantId } });
}

export async function updateMenuItem(restaurantId: string, itemId: string, data: Partial<Record<string, unknown>>) {
  const item = await prisma.menuItem.findFirst({ where: { id: itemId, restaurantId } });
  if (!item) throw NotFound('Menu item not found');
  return prisma.menuItem.update({ where: { id: itemId }, data });
}

export async function setAvailability(restaurantId: string, itemId: string, isAvailable: boolean) {
  const item = await prisma.menuItem.findFirst({ where: { id: itemId, restaurantId } });
  if (!item) throw NotFound('Menu item not found');
  return prisma.menuItem.update({ where: { id: itemId }, data: { isAvailable } });
}
