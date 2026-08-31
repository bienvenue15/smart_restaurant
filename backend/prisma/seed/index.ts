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
    await prisma.rolePermission.deleteMany({
      where: { role: role as never, permissionCode: { notIn: codes } },
    });
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
    // kitchen_display is on every plan: QR ordering without a prep screen
    // leaves kitchen staff with only the menu 86-list. Analytics stays premium.
    { planName: SubscriptionPlanName.TRIAL, displayName: 'Trial', priceMonthly: 0, priceYearly: 0, durationDays: 30, maxTables: 5, maxUsers: 5, maxMenuItems: 50, maxOrdersPerMonth: 200, features: ['basic_pos', 'qr_ordering', 'kitchen_display'] },
    { planName: SubscriptionPlanName.BASIC, displayName: 'Basic', priceMonthly: 50000, priceYearly: 500000, durationDays: 30, maxTables: 30, maxUsers: 15, maxMenuItems: 200, maxOrdersPerMonth: 2000, features: ['basic_pos', 'qr_ordering', 'kitchen_display'] },
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

/**
 * Bootstraps the one platform-level SUPER_ADMIN account. Unlike the demo
 * restaurant, this always runs (not gated behind SEED_DEMO_DATA) - without
 * it there is no way to reach the superadmin console at all on a fresh
 * deployment. Credentials come from env vars with dev-only fallback
 * defaults; the legacy app's equivalent bootstrap risk was a hardcoded
 * fallback EMAIL that bypassed the real auth check entirely
 * (docs/SECURITY_AUDIT.md #7) - this is a normal password-checked account,
 * just with a default password that must be rotated in any real deployment.
 */
async function seedSystemSettings() {
  const defaults = [
    { settingKey: 'maintenance_mode', settingValue: 'off', description: 'When "on", customer and staff APIs return 503' },
    {
      settingKey: 'business_hours',
      settingValue: JSON.stringify({
        mon: '09:00-22:00',
        tue: '09:00-22:00',
        wed: '09:00-22:00',
        thu: '09:00-22:00',
        fri: '09:00-22:00',
        sat: '09:00-23:00',
        sun: '10:00-21:00',
      }),
      description: 'Per-day open-close ranges. Informational only.',
    },
    { settingKey: 'backup_schedule', settingValue: '02:00 Africa/Kigali', description: 'Informational backup schedule label for operators' },
    { settingKey: 'backup_retention_days', settingValue: '30', description: 'Delete dumps older than this many days; also keep at most 10 files' },
  ];

  for (const setting of defaults) {
    await prisma.systemSetting.upsert({
      where: { settingKey: setting.settingKey },
      update: {},
      create: setting,
    });
  }
}

async function seedSuperAdmin() {
  const username = process.env.SUPERADMIN_USERNAME ?? 'superadmin';
  const existing = await prisma.staffUser.findUnique({ where: { username } });
  if (existing) return;

  const password = process.env.SUPERADMIN_PASSWORD;
  if (!password) {
    throw new Error('SUPERADMIN_PASSWORD is required to seed the platform admin account');
  }
  if (process.env.NODE_ENV === 'production' && password === 'ChangeMe123!') {
    throw new Error('Refusing to seed SUPERADMIN_PASSWORD=ChangeMe123! in production — set a unique password first');
  }

  const passwordHash = await bcrypt.hash(password, 10);
  const email = process.env.SUPERADMIN_EMAIL || null;
  await prisma.staffUser.create({
    data: { username, passwordHash, fullName: 'Platform Superadmin', role: 'SUPER_ADMIN', restaurantId: null, email },
  });
  // eslint-disable-next-line no-console
  console.log(`Seeded superadmin account "${username}" - rotate SUPERADMIN_PASSWORD before any real deployment.`);
}

async function main() {
  await seedPermissions();
  await seedSubscriptionPlans();
  await seedSystemSettings();
  await seedSuperAdmin();
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
