import bcrypt from 'bcryptjs';
import { prisma } from '@/config/prisma';
import { Unauthorized } from '@/utils/httpError';
import { signStaffAccessToken, signStaffRefreshToken, verifyStaffRefreshToken } from './jwt';

/**
 * Fixes two legacy gaps in the same flow:
 *  - staff login never rotated/regenerated the session on success
 *    (docs/SECURITY_AUDIT.md #11) — here, every login issues a fresh
 *    access+refresh pair, and refresh rotates the token again.
 *  - superadmin auth had a hardcoded email bypass alongside the DB check
 *    (docs/SECURITY_AUDIT.md #7) — there is no bypass path here at all,
 *    SUPER_ADMIN is just another row with that role.
 */
export async function login(username: string, password: string) {
  const staff = await prisma.staffUser.findUnique({ where: { username } });
  if (!staff || !staff.isActive) throw Unauthorized('Invalid username or password');

  const valid = await bcrypt.compare(password, staff.passwordHash);
  if (!valid) throw Unauthorized('Invalid username or password');

  await prisma.staffUser.update({ where: { id: staff.id }, data: { lastLoginAt: new Date() } });
  await prisma.staffActivityLog.create({
    data: { staffId: staff.id, action: 'login', description: 'Staff login' },
  });

  const accessToken = signStaffAccessToken({ sub: staff.id, role: staff.role, restaurantId: staff.restaurantId });
  const refreshToken = signStaffRefreshToken({ sub: staff.id });

  return {
    accessToken,
    refreshToken,
    staff: { id: staff.id, fullName: staff.fullName, role: staff.role, restaurantId: staff.restaurantId },
  };
}

export async function refresh(refreshToken: string) {
  let payload: { sub: string };
  try {
    payload = verifyStaffRefreshToken(refreshToken);
  } catch {
    throw Unauthorized('Invalid or expired refresh token');
  }

  const staff = await prisma.staffUser.findUnique({ where: { id: payload.sub } });
  if (!staff || !staff.isActive) throw Unauthorized('Account no longer active');

  const accessToken = signStaffAccessToken({ sub: staff.id, role: staff.role, restaurantId: staff.restaurantId });
  const nextRefreshToken = signStaffRefreshToken({ sub: staff.id });

  return { accessToken, refreshToken: nextRefreshToken };
}

export async function logout(staffId: string): Promise<void> {
  await prisma.staffActivityLog.create({
    data: { staffId, action: 'logout', description: 'Staff logout' },
  });
}
