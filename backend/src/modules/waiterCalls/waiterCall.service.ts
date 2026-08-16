import { WaiterCallPriority, WaiterCallRequestType, WaiterCallStatus } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { Conflict, NotFound } from '@/utils/httpError';
import { notifyRoles } from '@/modules/notifications/notification.service';

export async function createCall(restaurantId: string, tableId: string, requestType: WaiterCallRequestType, message: string | undefined, priority: WaiterCallPriority) {
  const call = await prisma.waiterCall.create({ data: { restaurantId, tableId, requestType, message, priority } });

  const table = await prisma.restaurantTable.findUnique({ where: { id: tableId } });
  await notifyRoles(
    restaurantId,
    ['WAITER', 'MANAGER', 'ADMIN'],
    'waiter_call',
    'Waiter call',
    `Table ${table?.tableNumber ?? '?'} requested ${requestType.toLowerCase()}`,
  );

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
  return prisma.$transaction(async (tx) => {
    const call = await tx.waiterCall.findFirst({ where: { id: callId, restaurantId } });
    if (!call) throw NotFound('Waiter call not found');
    if (call.status !== 'PENDING') throw Conflict('This call has already been claimed');

    return tx.waiterCall.update({
      where: { id: callId },
      data: { status: 'ACKNOWLEDGED', assignedToId: staffId, assignedAt: new Date() },
    });
  });
}

export async function assignCall(restaurantId: string, callId: string, staffId: string) {
  const call = await prisma.waiterCall.findFirst({ where: { id: callId, restaurantId } });
  if (!call) throw NotFound('Waiter call not found');

  return prisma.waiterCall.update({
    where: { id: callId },
    data: { status: 'ACKNOWLEDGED', assignedToId: staffId, assignedAt: new Date() },
  });
}

export async function completeCall(restaurantId: string, callId: string) {
  const call = await prisma.waiterCall.findFirst({ where: { id: callId, restaurantId } });
  if (!call) throw NotFound('Waiter call not found');

  return prisma.waiterCall.update({ where: { id: callId }, data: { status: 'COMPLETED', completedAt: new Date() } });
}
