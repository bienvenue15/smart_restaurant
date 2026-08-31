/**
 * Dev-only convenience: sets one staff account per role on Heaven Restaurant
 * Kigali (the fully-populated imported restaurant) to a known password, so
 * every role can be tested without knowing the legacy bcrypt hash's
 * plaintext. Username == role name; NOT for production use.
 *
 * Usage: npm run seed:test-credentials
 */
import { PrismaClient, StaffRole } from '@prisma/client';
import bcrypt from 'bcryptjs';

const prisma = new PrismaClient();
const TEST_PASSWORD = 'password';

const ROLE_USERNAMES: Record<Exclude<StaffRole, 'SUPER_ADMIN'>, string> = {
  ADMIN: 'admin',
  MANAGER: 'manager',
  WAITER: 'waiter',
  KITCHEN: 'kitchen',
  CASHIER: 'cashier',
};

async function main() {
  const restaurant = await prisma.restaurant.findFirst({ where: { name: 'Heaven Restaurant Kigali' } });
  if (!restaurant) throw new Error('Heaven Restaurant Kigali not found — run the legacy import first');

  const passwordHash = await bcrypt.hash(TEST_PASSWORD, 10);

  // waiter1 -> waiter, so username matches the role name exactly
  const legacyWaiter = await prisma.staffUser.findUnique({ where: { username: 'waiter1' } });
  if (legacyWaiter && legacyWaiter.restaurantId === restaurant.id) {
    await prisma.staffUser.update({ where: { id: legacyWaiter.id }, data: { username: 'waiter' } });
  }

  for (const [role, username] of Object.entries(ROLE_USERNAMES)) {
    const staff = await prisma.staffUser.findFirst({ where: { restaurantId: restaurant.id, role: role as StaffRole } });
    if (!staff) {
      console.warn(`  ! no ${role} account found on ${restaurant.name}, skipped`);
      continue;
    }
    await prisma.staffUser.update({
      where: { id: staff.id },
      data: { username, passwordHash, ...(role === 'CASHIER' ? { canHandleCash: true } : {}) },
    });
    console.log(`  ${role.padEnd(10)} username=${username}  password=${TEST_PASSWORD}`);
  }

  console.log(`\nSuperadmin login is unchanged — use the SUPERADMIN_USERNAME/SUPERADMIN_PASSWORD from .env.`);
}

main()
  .catch((err) => {
    console.error(err);
    process.exitCode = 1;
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
