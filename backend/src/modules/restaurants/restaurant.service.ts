import crypto from 'node:crypto';
import bcrypt from 'bcryptjs';
import { prisma } from '@/config/prisma';
import { Conflict, NotFound } from '@/utils/httpError';

const DEFAULT_CATEGORIES = ['Starters', 'Main Courses', 'Desserts', 'Drinks'];
const TRIAL_DURATION_DAYS = 30;

function slugify(name: string): string {
  return name
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9-]/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
}

/**
 * Random suffix on the slug — legacy's anti-enumeration measure for public
 * QR-menu URLs (docs/CURRENT_SYSTEM_AUDIT.md §4), preserved here.
 */
async function generateUniqueSlug(name: string): Promise<string> {
  const base = slugify(name);
  for (let attempt = 0; attempt < 20; attempt++) {
    const suffix = crypto.randomBytes(3).toString('hex');
    const candidate = `${base}-${suffix}`;
    const existing = await prisma.restaurant.findUnique({ where: { slug: candidate } });
    if (!existing) return candidate;
  }
  return `${base}-${Date.now()}`;
}

/**
 * Single, consolidated onboarding implementation. The legacy app had this
 * "create restaurant + admin user + seed default categories" bootstrap
 * duplicated across register.php (self-service) and superadmin.php
 * (admin-driven) — docs/CURRENT_SYSTEM_AUDIT.md §4 flags this as logic
 * that should be a single service. The admin-driven variant (which
 * auto-generates a password and emails it in plaintext in the legacy app,
 * a security smell per docs/SECURITY_AUDIT.md #11) is implemented
 * separately in modules/admin using this same function with a
 * generated password, but never emails it in plaintext.
 */
export async function registerRestaurant(data: {
  restaurantName: string;
  ownerName: string;
  email: string;
  password: string;
  phone?: string;
  tin?: string;
  address?: string;
  city?: string;
}) {
  const existingEmail = await prisma.restaurant.findUnique({ where: { email: data.email } });
  if (existingEmail) throw Conflict('A restaurant with this email already exists');

  const slug = await generateUniqueSlug(data.restaurantName);
  const passwordHash = await bcrypt.hash(data.password, 10);
  const today = new Date();
  const subscriptionEnd = new Date(today.getTime() + TRIAL_DURATION_DAYS * 24 * 60 * 60 * 1000);

  return prisma.$transaction(async (tx) => {
    const restaurant = await tx.restaurant.create({
      data: {
        name: data.restaurantName,
        slug,
        email: data.email,
        phone: data.phone,
        tin: data.tin,
        address: data.address,
        city: data.city ?? 'Kigali',
        subscriptionPlan: 'TRIAL',
        subscriptionStart: today,
        subscriptionEnd,
      },
    });

    await tx.staffUser.create({
      data: {
        restaurantId: restaurant.id,
        username: data.email,
        passwordHash,
        fullName: data.ownerName,
        email: data.email,
        role: 'ADMIN',
      },
    });

    await tx.menuCategory.createMany({
      data: DEFAULT_CATEGORIES.map((name, index) => ({ restaurantId: restaurant.id, name, displayOrder: index })),
    });

    return { id: restaurant.id, slug: restaurant.slug, name: restaurant.name };
  });
}

export async function getRestaurant(restaurantId: string) {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) throw NotFound('Restaurant not found');
  return restaurant;
}

export async function updateRestaurant(restaurantId: string, data: Partial<Record<string, unknown>>) {
  const restaurant = await prisma.restaurant.findUnique({ where: { id: restaurantId } });
  if (!restaurant) throw NotFound('Restaurant not found');
  return prisma.restaurant.update({ where: { id: restaurantId }, data });
}

export async function getSettings(restaurantId: string) {
  const rows = await prisma.restaurantSetting.findMany({ where: { restaurantId } });
  return Object.fromEntries(rows.map((r) => [r.settingKey, r.settingValue]));
}

export async function setSetting(restaurantId: string, settingKey: string, settingValue: string | null) {
  return prisma.restaurantSetting.upsert({
    where: { restaurantId_settingKey: { restaurantId, settingKey } },
    update: { settingValue },
    create: { restaurantId, settingKey, settingValue },
  });
}
