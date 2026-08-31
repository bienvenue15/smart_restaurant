import { WaiterCallPriority, WaiterCallRequestType, WaiterCallStatus } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { Conflict, NotFound } from '@/utils/httpError';
import { notifyRoles } from '@/modules/notifications/notification.service';
import { notifyPhone } from '@/services/messaging.service';
import { publishRealtime } from '@/services/realtime.service';

export async function createCall(restaurantId: string, tableId: string, requestType: WaiterCallRequestType, message: string | undefined, priority: WaiterCallPriority) {
  const call = await prisma.waiterCall.create({ data: { restaurantId, tableId, requestType, message, priority } });

  const table = await prisma.restaurantTable.findUnique({ where: { id: tableId } });
  const text = `Table ${table?.tableNumber ?? '?'} requested ${requestType.toLowerCase()}`;
  // title/message stay in English as a DB record and SMS/email fallback;
  // the frontend renders from `type` + `data` via i18n instead of these
  // (see NotificationBell.vue / useNotificationText.ts).
  await notifyRoles(restaurantId, ['WAITER', 'MANAGER', 'ADMIN'], 'waiter_call', 'Waiter call', text, {
    tableNumber: table?.tableNumber ?? '?',
    requestType,
  });

  const floorStaff = await prisma.staffUser.findMany({
    where: { restaurantId, role: { in: ['WAITER', 'MANAGER', 'ADMIN'] }, isActive: true, NOT: { phone: null } },
    select: { phone: true },
  });
  await Promise.all(floorStaff.map((row) => notifyPhone(row.phone, `Smart Restaurant: ${text}`)));

  return call;
}

export async function listCalls(restaurantId: string, status?: WaiterCallStatus) {
  return prisma.waiterCall.findMany({
    where: { restaurantId, ...(status ? { status } : {}) },
    include: { table: { select: { tableNumber: true } }, assignedTo: { select: { fullName: true } } },
    orderBy: { createdAt: 'desc' },
  });
}

/**
 * First-accept-wins, race-safe via a serializable transaction that
 * re-checks status before updating — replaces the legacy's raw
 * `SELECT ... FOR UPDATE` row lock (docs/CURRENT_SYSTEM_AUDIT.md §5) with
 * Prisma's equivalent optimistic-then-verify pattern.
 */
export async function acceptCall(restaurantId: string, callId: string, staffId: string) {
  const updated = await prisma.$transaction(async (tx) => {
    const call = await tx.waiterCall.findFirst({ where: { id: callId, restaurantId } });
    if (!call) throw NotFound('Waiter call not found');
    if (call.status !== 'PENDING') throw Conflict('This call has already been claimed');

    return tx.waiterCall.update({
      where: { id: callId },
      data: { status: 'ACKNOWLEDGED', assignedToId: staffId, assignedAt: new Date() },
    });
  });
  // Lets the staff-side "ring until picked up" alert stop immediately
  // instead of waiting out its poll interval.
  await publishRealtime({ channel: 'staff', type: 'waiter_call_updated', restaurantId });
  return updated;
}

export async function assignCall(restaurantId: string, callId: string, staffId: string) {
  const call = await prisma.waiterCall.findFirst({ where: { id: callId, restaurantId } });
  if (!call) throw NotFound('Waiter call not found');

  const updated = await prisma.waiterCall.update({
    where: { id: callId },
    data: { status: 'ACKNOWLEDGED', assignedToId: staffId, assignedAt: new Date() },
  });
  await publishRealtime({ channel: 'staff', type: 'waiter_call_updated', restaurantId });
  return updated;
}

export async function completeCall(restaurantId: string, callId: string) {
  const call = await prisma.waiterCall.findFirst({ where: { id: callId, restaurantId } });
  if (!call) throw NotFound('Waiter call not found');

  const updated = await prisma.waiterCall.update({ where: { id: callId }, data: { status: 'COMPLETED', completedAt: new Date() } });
  await publishRealtime({ channel: 'staff', type: 'waiter_call_updated', restaurantId });
  return updated;
}
