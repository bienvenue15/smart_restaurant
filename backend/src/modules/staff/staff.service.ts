import bcrypt from 'bcryptjs';
import { StaffRole } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { Conflict, NotFound } from '@/utils/httpError';
import { LIMIT_CHECKERS } from '@/modules/subscriptions/subscription.service';
import { publishRealtime } from '@/services/realtime.service';

export async function listStaff(restaurantId: string) {
  return prisma.staffUser.findMany({
    where: { restaurantId },
    select: {
      id: true,
      username: true,
      fullName: true,
      email: true,
      phone: true,
      role: true,
      isActive: true,
      canHandleCash: true,
      lastLoginAt: true,
      createdAt: true,
    },
    orderBy: { fullName: 'asc' },
  });
}

export async function createStaff(
  restaurantId: string,
  data: { username: string; password: string; fullName: string; email?: string; phone?: string; role: StaffRole; canHandleCash?: boolean; maxDiscountPercent?: number },
) {
  await LIMIT_CHECKERS.users(restaurantId);

  const existing = await prisma.staffUser.findUnique({ where: { username: data.username } });
  if (existing) throw Conflict('Username already taken');

  const passwordHash = await bcrypt.hash(data.password, 10);
  const { password: _password, canHandleCash, ...rest } = data;
  const created = await prisma.staffUser.create({
    data: {
      ...rest,
      restaurantId,
      passwordHash,
      canHandleCash: canHandleCash ?? data.role === 'CASHIER',
    },
    select: { id: true, username: true, fullName: true, role: true },
  });
  await publishRealtime({ channel: 'staff', type: 'team_updated', restaurantId });
  return created;
}

export async function updateStaff(restaurantId: string, staffId: string, data: Partial<Record<string, unknown>>) {
  const staff = await prisma.staffUser.findFirst({ where: { id: staffId, restaurantId } });
  if (!staff) throw NotFound('Staff member not found');
  if (data.isActive === true && !staff.isActive) {
    await LIMIT_CHECKERS.users(restaurantId);
  }
  const updated = await prisma.staffUser.update({ where: { id: staffId }, data });
  await publishRealtime({ channel: 'staff', type: 'team_updated', restaurantId });
  return updated;
}

/**
 * Fixes a legacy gap: the equivalent guard existed only as a MySQL trigger
 * (`trg_prevent_staff_delete_with_open_session`, docs/DATABASE_MIGRATION_PLAN.md
 * §"Reimplementing legacy triggers") which silently disappears when moving
 * to Prisma unless reimplemented in the application layer — here.
 */
export async function deleteStaff(restaurantId: string, staffId: string) {
  const staff = await prisma.staffUser.findFirst({ where: { id: staffId, restaurantId } });
  if (!staff) throw NotFound('Staff member not found');

  const openCashSession = await prisma.cashSession.findFirst({
    where: { staffId, status: { in: ['OPEN', 'AUDITING'] } },
  });
  if (openCashSession) throw Conflict('Cannot delete a staff member with an open cash session');

  await prisma.staffUser.update({ where: { id: staffId }, data: { isActive: false } });
  await publishRealtime({ channel: 'staff', type: 'team_updated', restaurantId });
}

export async function clockIn(staffId: string, restaurantId: string) {
  const openShift = await prisma.staffShift.findFirst({ where: { staffId, clockOut: null, status: 'ACTIVE' } });
  if (openShift) throw Conflict('Already clocked in');

  const shift = await prisma.staffShift.create({ data: { staffId, restaurantId } });
  await prisma.staffActivityLog.create({ data: { staffId, action: 'clock_in' } });
  await publishRealtime({ channel: 'staff', type: 'team_updated', restaurantId });
  return shift;
}

export async function clockOut(staffId: string) {
  const openShift = await prisma.staffShift.findFirst({ where: { staffId, clockOut: null, status: 'ACTIVE' } });
  if (!openShift) throw Conflict('Not currently clocked in');

  const shift = await prisma.staffShift.update({
    where: { id: openShift.id },
    data: { clockOut: new Date(), status: 'COMPLETED' },
  });
  await prisma.staffActivityLog.create({ data: { staffId, action: 'clock_out' } });
  await publishRealtime({ channel: 'staff', type: 'team_updated', restaurantId: openShift.restaurantId });
  return shift;
}

export async function getShiftStatus(staffId: string) {
  const openShift = await prisma.staffShift.findFirst({ where: { staffId, clockOut: null, status: 'ACTIVE' } });
  return { onShift: Boolean(openShift), shift: openShift };
}
