import { PrismaClient, SubscriptionPlanName } from '@prisma/client';
import bcrypt from 'bcryptjs';
import { PERMISSIONS, ROLE_PERMISSIONS } from './permissions';

const prisma = new PrismaClient();

async function seedPermissions() {
  for (const permission of PERMISSIONS) {
    await prisma.permission.upsert({
      where: { code: permission.code },
      update: permission,
      create: permission,
    });
  }

  for (const [role, codes] of Object.entries(ROLE_PERMISSIONS)) {
    for (const permissionCode of codes) {
      await prisma.rolePermission.upsert({
        where: { role_permissionCode: { role: role as never, permissionCode } },
        update: {},
        create: { role: role as never, permissionCode },
      });
    }
  }
}

async function seedSubscriptionPlans() {
  const plans = [
    { planName: SubscriptionPlanName.TRIAL, displayName: 'Trial', priceMonthly: 0, priceYearly: 0, durationDays: 30, maxTables: 5, maxUsers: 5, maxMenuItems: 50, maxOrdersPerMonth: 200, features: ['basic_pos', 'qr_ordering'] },
    { planName: SubscriptionPlanName.BASIC, displayName: 'Basic', priceMonthly: 50000, priceYearly: 500000, durationDays: 30, maxTables: 30, maxUsers: 15, maxMenuItems: 200, maxOrdersPerMonth: 2000, features: ['basic_pos', 'qr_ordering'] },
    { planName: SubscriptionPlanName.PREMIUM, displayName: 'Premium', priceMonthly: 150000, priceYearly: 1500000, durationDays: 30, maxTables: 100, maxUsers: 50, maxMenuItems: 1000, maxOrdersPerMonth: 20000, features: ['basic_pos', 'qr_ordering', 'inventory', 'analytics', 'kitchen_display'] },
    { planName: SubscriptionPlanName.ENTERPRISE, displayName: 'Enterprise', priceMonthly: 500000, priceYearly: 5000000, durationDays: 30, maxTables: 1000, maxUsers: 500, maxMenuItems: 10000, maxOrdersPerMonth: 1000000, features: ['basic_pos', 'qr_ordering', 'inventory', 'analytics', 'kitchen_display', 'multi_location', 'api_access', 'custom_reports'] },
  ];

  for (const plan of plans) {
    await prisma.subscriptionPlan.upsert({ where: { planName: plan.planName }, update: plan, create: plan });
  }
}

async function seedDemoRestaurant() {
  const existing = await prisma.restaurant.findUnique({ where: { slug: 'demo-restaurant' } });
  if (existing) return;

  const restaurant = await prisma.restaurant.create({
    data: {
      name: 'Demo Restaurant',
      slug: 'demo-restaurant',
      email: 'demo@smartresto.local',
      subscriptionPlan: SubscriptionPlanName.TRIAL,
      subscriptionStart: new Date(),
      subscriptionEnd: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000),
    },
  });

  const passwordHash = await bcrypt.hash('ChangeMe123!', 10);
  await prisma.staffUser.create({
    data: {
      restaurantId: restaurant.id,
      username: 'demo-admin',
      passwordHash,
      fullName: 'Demo Admin',
      role: 'ADMIN',
    },
  });

  const category = await prisma.menuCategory.create({
    data: { restaurantId: restaurant.id, name: 'Starters', displayOrder: 0 },
  });

  await prisma.menuItem.create({
    data: {
      restaurantId: restaurant.id,
      categoryId: category.id,
      name: 'Sample Spring Rolls',
      price: 3500,
      preparationTime: 10,
    },
  });

  await prisma.restaurantTable.create({
    data: {
      restaurantId: restaurant.id,
      tableNumber: 'T1',
      qrCode: `demo-restaurant-t1-${Date.now()}`,
      seats: 4,
    },
  });
}

async function main() {
  await seedPermissions();
  await seedSubscriptionPlans();
  if (process.env.SEED_DEMO_DATA === 'true') {
    await seedDemoRestaurant();
  }
}

main()
  .catch((err) => {
    console.error(err);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
