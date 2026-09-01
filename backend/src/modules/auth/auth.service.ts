import crypto from 'node:crypto';
import bcrypt from 'bcryptjs';
import { StaffRole } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { config } from '@/config/env';
import { BadRequest, Forbidden, Unauthorized } from '@/utils/httpError';
import { sendMail } from '@/services/mail.service';
import { notifyPhone } from '@/services/messaging.service';
import {
  decryptTotpSecret,
  encryptTotpSecret,
  generateTotpSecret,
  totpSetupPayload,
  verifyTotpCode,
} from '@/utils/totp';
import { listPermissionsForRole } from '@/middleware/permission';
import { getEntitlements } from '@/modules/subscriptions/subscription.service';
import {
  signStaffAccessToken,
  signStaffRefreshToken,
  signTwoFactorPendingToken,
  verifyStaffRefreshToken,
  verifyTwoFactorPendingToken,
} from './jwt';
import { shouldChallengeTwoFactor } from './twoFactor';

const RESET_TTL_MS = 60 * 60 * 1000;

function hashToken(token: string) {
  return crypto.createHash('sha256').update(token).digest('hex');
}

async function toStaffSession(staff: {
  id: string;
  fullName: string;
  role: StaffRole;
  restaurantId: string | null;
  canHandleCash: boolean;
}) {
  const [restaurant, permissions, plan] = await Promise.all([
    staff.restaurantId
      ? prisma.restaurant.findUnique({
          where: { id: staff.restaurantId },
          select: { name: true, logoUrl: true, primaryColor: true, secondaryColor: true },
        })
      : Promise.resolve(null),
    listPermissionsForRole(staff.role),
    staff.restaurantId
      ? getEntitlements(staff.restaurantId).catch(() => null)
      : Promise.resolve(null),
  ]);

  return {
    id: staff.id,
    fullName: staff.fullName,
    role: staff.role,
    restaurantId: staff.restaurantId,
    restaurant,
    permissions,
    canHandleCash: staff.canHandleCash,
    plan: plan
      ? {
          name: plan.planName,
          displayName: plan.displayName,
          features: plan.features,
          limits: plan.limits,
          subscriptionActive: plan.subscriptionActive,
          subscriptionEnd: plan.subscriptionEnd,
        }
      : null,
  };
}

async function issueSession(staff: {
  id: string;
  fullName: string;
  role: StaffRole;
  restaurantId: string | null;
  canHandleCash: boolean;
}) {
  await prisma.staffUser.update({ where: { id: staff.id }, data: { lastLoginAt: new Date() } });
  await prisma.staffActivityLog.create({
    data: { staffId: staff.id, action: 'login', description: 'Staff login' },
  });

  const accessToken = signStaffAccessToken({ sub: staff.id, role: staff.role, restaurantId: staff.restaurantId });
  const refreshToken = signStaffRefreshToken({ sub: staff.id });

  return {
    requiresTwoFactor: false as const,
    accessToken,
    refreshToken,
    staff: await toStaffSession(staff),
  };
}

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
  const identifier = username.trim();
  let staff = await prisma.staffUser.findUnique({ where: { username: identifier } });
  if (!staff && identifier.includes('@')) {
    staff = await prisma.staffUser.findFirst({
      where: { email: { equals: identifier, mode: 'insensitive' } },
    });
  }
  if (!staff || !staff.isActive) throw Unauthorized('Invalid username or password');

  const valid = await bcrypt.compare(password, staff.passwordHash);
  if (!valid) throw Unauthorized('Invalid username or password');

  if (shouldChallengeTwoFactor(staff)) {
    return {
      requiresTwoFactor: true as const,
      pendingToken: signTwoFactorPendingToken({ sub: staff.id }),
      staff: await toStaffSession(staff),
    };
  }

  return issueSession(staff);
}

function assertSuperAdminTwoFactor(staff: { role: StaffRole }) {
  if (staff.role !== StaffRole.SUPER_ADMIN) {
    throw Forbidden('Two-factor authentication is only available for superadmin');
  }
}

export async function completeTwoFactorLogin(pendingToken: string, code: string) {
  let sub: string;
  try {
    ({ sub } = verifyTwoFactorPendingToken(pendingToken));
  } catch {
    throw Unauthorized('Two-factor challenge expired — sign in again');
  }

  const staff = await prisma.staffUser.findUnique({ where: { id: sub } });
  if (!staff || !staff.isActive || !shouldChallengeTwoFactor(staff) || !staff.totpSecret) {
    throw Unauthorized('Two-factor authentication is not available for this account');
  }

  const secret = decryptTotpSecret(staff.totpSecret);
  if (!verifyTotpCode(staff.username, secret, code)) {
    throw Unauthorized('Invalid authenticator code');
  }

  return issueSession(staff);
}

export async function getTwoFactorStatus(staffId: string) {
  const staff = await prisma.staffUser.findUnique({
    where: { id: staffId },
    select: { totpEnabled: true, role: true },
  });
  if (!staff) throw Unauthorized();
  assertSuperAdminTwoFactor(staff);
  return { enabled: staff.totpEnabled };
}

export async function setupTwoFactor(staffId: string) {
  const staff = await prisma.staffUser.findUnique({ where: { id: staffId } });
  if (!staff || !staff.isActive) throw Unauthorized();
  assertSuperAdminTwoFactor(staff);
  if (staff.totpEnabled) throw BadRequest('Two-factor authentication is already enabled');

  const secret = generateTotpSecret();
  await prisma.staffUser.update({ where: { id: staffId }, data: { totpSecret: encryptTotpSecret(secret) } });
  return totpSetupPayload(staff.username, secret);
}

export async function enableTwoFactor(staffId: string, code: string) {
  const staff = await prisma.staffUser.findUnique({ where: { id: staffId } });
  if (!staff || !staff.isActive) throw Unauthorized();
  assertSuperAdminTwoFactor(staff);
  if (staff.totpEnabled) throw BadRequest('Two-factor authentication is already enabled');
  if (!staff.totpSecret) throw BadRequest('Start two-factor setup first');

  const secret = decryptTotpSecret(staff.totpSecret);
  if (!verifyTotpCode(staff.username, secret, code)) throw BadRequest('Invalid authenticator code');

  await prisma.staffUser.update({ where: { id: staffId }, data: { totpEnabled: true } });
  await prisma.staffActivityLog.create({
    data: { staffId, action: '2fa_enabled', description: 'Authenticator app 2FA enabled' },
  });
  return { enabled: true };
}

export async function cancelTwoFactorSetup(staffId: string) {
  const staff = await prisma.staffUser.findUnique({ where: { id: staffId } });
  if (!staff || !staff.isActive) throw Unauthorized();
  assertSuperAdminTwoFactor(staff);
  if (staff.totpEnabled) throw BadRequest('Two-factor authentication is already enabled');

  await prisma.staffUser.update({ where: { id: staffId }, data: { totpSecret: null } });
  return { enabled: false };
}

export async function disableTwoFactor(staffId: string, password: string, code: string) {
  const staff = await prisma.staffUser.findUnique({ where: { id: staffId } });
  if (!staff || !staff.isActive) throw Unauthorized();
  assertSuperAdminTwoFactor(staff);
  if (!staff.totpEnabled || !staff.totpSecret) throw BadRequest('Two-factor authentication is not enabled');

  const valid = await bcrypt.compare(password, staff.passwordHash);
  if (!valid) throw BadRequest('Current password is incorrect');
  if (!verifyTotpCode(staff.username, decryptTotpSecret(staff.totpSecret), code)) {
    throw BadRequest('Invalid authenticator code');
  }

  await prisma.staffUser.update({ where: { id: staffId }, data: { totpEnabled: false, totpSecret: null } });
  await prisma.staffActivityLog.create({
    data: { staffId, action: '2fa_disabled', description: 'Authenticator app 2FA disabled' },
  });
  return { enabled: false };
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

  return {
    accessToken,
    refreshToken: nextRefreshToken,
    staff: await toStaffSession(staff),
  };
}

export async function logout(staffId: string): Promise<void> {
  await prisma.staffActivityLog.create({
    data: { staffId, action: 'logout', description: 'Staff logout' },
  });
}

/**
 * Always succeeds from the caller's point of view so usernames/emails
 * cannot be enumerated. A mail/SMS is only actually sent when the account
 * exists, is active, and has a contact channel.
 */
export async function requestPasswordReset(identifier: string): Promise<void> {
  const staff = await prisma.staffUser.findFirst({
    where: {
      isActive: true,
      OR: [{ username: identifier }, { email: { equals: identifier, mode: 'insensitive' } }],
    },
  });
  if (!staff?.email && !staff?.phone) return;

  await prisma.passwordResetToken.deleteMany({ where: { staffId: staff.id, usedAt: null } });

  const token = crypto.randomBytes(32).toString('base64url');
  await prisma.passwordResetToken.create({
    data: {
      staffId: staff.id,
      tokenHash: hashToken(token),
      expiresAt: new Date(Date.now() + RESET_TTL_MS),
    },
  });

  const resetUrl = `${config.frontendUrl.replace(/\/$/, '')}/staff/reset-password?token=${encodeURIComponent(token)}`;
  if (staff.email) {
    await sendMail({
      to: staff.email,
      subject: 'Reset your Smart Restaurant password',
      text: `Hi ${staff.fullName},\n\nWe received a request to reset your password. Open this link within 1 hour:\n\n${resetUrl}\n\nIf you did not ask for this, you can ignore the email.`,
    });
  }
  if (staff.phone) {
    await notifyPhone(staff.phone, `Smart Restaurant password reset (1 hour): ${resetUrl}`);
  }
}

export async function resetPassword(token: string, password: string): Promise<void> {
  const row = await prisma.passwordResetToken.findUnique({ where: { tokenHash: hashToken(token) } });
  if (!row || row.usedAt || row.expiresAt.getTime() < Date.now()) {
    throw BadRequest('This reset link is invalid or has expired');
  }

  const passwordHash = await bcrypt.hash(password, 10);
  await prisma.$transaction([
    prisma.staffUser.update({ where: { id: row.staffId }, data: { passwordHash } }),
    prisma.passwordResetToken.update({ where: { id: row.id }, data: { usedAt: new Date() } }),
    prisma.passwordResetToken.deleteMany({ where: { staffId: row.staffId, usedAt: null, id: { not: row.id } } }),
    prisma.staffActivityLog.create({
      data: { staffId: row.staffId, action: 'password_reset', description: 'Password reset via emailed link' },
    }),
  ]);
}

export async function changePassword(staffId: string, currentPassword: string, newPassword: string): Promise<void> {
  const staff = await prisma.staffUser.findUnique({ where: { id: staffId } });
  if (!staff || !staff.isActive) throw Unauthorized();

  const valid = await bcrypt.compare(currentPassword, staff.passwordHash);
  if (!valid) throw BadRequest('Current password is incorrect');
  if (currentPassword === newPassword) throw BadRequest('New password must be different from the current password');

  const passwordHash = await bcrypt.hash(newPassword, 10);
  await prisma.staffUser.update({ where: { id: staffId }, data: { passwordHash } });
  await prisma.staffActivityLog.create({
    data: { staffId, action: 'password_change', description: 'Password changed while signed in' },
  });
}
