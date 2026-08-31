import { prisma } from '@/config/prisma';
import { Conflict, NotFound } from '@/utils/httpError';
import { signCustomerSessionToken } from '@/modules/auth/jwt';
import { hasFeature, isSubscriptionActive } from '@/modules/subscriptions/subscription.service';

/** 120-minute device-table lock, preserved from legacy (docs/CURRENT_SYSTEM_AUDIT.md §4). */
const DEVICE_LOCK_DURATION_MINUTES = 120;

interface ScanResult {
  sessionToken: string;
  restaurant: { id: string; name: string; currency: string; logoUrl: string | null; primaryColor: string };
  table: { id: string; tableNumber: string };
}

/**
 * QR scan → table resolution → device lock → signed session token. This is
 * the customer flow's actual entry point, missing from the legacy audit's
 * "dead SSE plumbing" critique of the rest of the real-time layer — this
 * part of the legacy flow was real and is ported faithfully, with the
 * fingerprint demoted to a secondary signal (see deviceFingerprint.ts) and
 * a signed token as the real authority (docs/SECURITY_AUDIT.md #9).
 */
export async function scanQrCode(qrCode: string, deviceFingerprint: string): Promise<ScanResult> {
  const table = await prisma.restaurantTable.findUnique({
    where: { qrCode },
    include: { restaurant: true },
  });
  if (!table) throw NotFound('Table not found for this QR code');

  const { restaurant } = table;
  const today = new Date();
  if (!(await isSubscriptionActive(restaurant.id))) {
    throw Conflict('This restaurant is temporarily unavailable');
  }
  if (!(await hasFeature(restaurant.id, 'qr_ordering'))) {
    throw Conflict('QR ordering is not included in this restaurant’s plan');
  }

  await cleanupExpiredLocks(table.restaurantId);

  // Block if this device is already locked to a DIFFERENT table.
  const deviceLockElsewhere = await prisma.deviceTableLock.findFirst({
    where: { deviceFingerprint, isActive: true, tableId: { not: table.id }, expiresAt: { gt: today } },
  });
  if (deviceLockElsewhere) {
    throw Conflict('Your device is already active at another table. Please finish that session first.');
  }

  // Block if this table is actively locked by a DIFFERENT device with an unpaid order in progress.
  const conflictingLock = await prisma.deviceTableLock.findFirst({
    where: { tableId: table.id, isActive: true, deviceFingerprint: { not: deviceFingerprint }, expiresAt: { gt: today } },
  });
  if (conflictingLock) {
    const hasActiveOrder = await prisma.order.findFirst({
      where: { tableId: table.id, status: { notIn: ['COMPLETED', 'CANCELLED'] } },
    });
    if (hasActiveOrder) throw Conflict('This table is currently in use by another device');
  }

  const expiresAt = new Date(today.getTime() + DEVICE_LOCK_DURATION_MINUTES * 60 * 1000);

  const existingOwnLock = await prisma.deviceTableLock.findFirst({
    where: { tableId: table.id, deviceFingerprint, isActive: true },
  });

  const lock = existingOwnLock
    ? await prisma.deviceTableLock.update({
        where: { id: existingOwnLock.id },
        data: { expiresAt, lastActivity: today },
      })
    : await prisma.deviceTableLock.create({
        data: {
          restaurantId: table.restaurantId,
          tableId: table.id,
          deviceFingerprint,
          expiresAt,
        },
      });

  const sessionToken = signCustomerSessionToken({
    sub: lock.id,
    restaurantId: table.restaurantId,
    tableId: table.id,
  });

  return {
    sessionToken,
    restaurant: {
      id: restaurant.id,
      name: restaurant.name,
      currency: restaurant.currency,
      logoUrl: restaurant.logoUrl,
      primaryColor: restaurant.primaryColor,
    },
    table: { id: table.id, tableNumber: table.tableNumber },
  };
}

export async function extendLockOnActivity(deviceTableLockId: string): Promise<void> {
  const expiresAt = new Date(Date.now() + DEVICE_LOCK_DURATION_MINUTES * 60 * 1000);
  await prisma.deviceTableLock.updateMany({
    where: { id: deviceTableLockId, isActive: true },
    data: { expiresAt, lastActivity: new Date() },
  });
}

export async function endSession(deviceTableLockId: string): Promise<void> {
  await prisma.deviceTableLock.updateMany({
    where: { id: deviceTableLockId, isActive: true },
    data: { isActive: false },
  });
}

async function cleanupExpiredLocks(restaurantId: string): Promise<void> {
  await prisma.deviceTableLock.updateMany({
    where: { restaurantId, isActive: true, expiresAt: { lt: new Date() } },
    data: { isActive: false },
  });
}
