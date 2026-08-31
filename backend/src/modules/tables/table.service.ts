import crypto from 'node:crypto';
import { TableStatus } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { Conflict, NotFound } from '@/utils/httpError';
import { LIMIT_CHECKERS } from '@/modules/subscriptions/subscription.service';
import { publishRealtime } from '@/services/realtime.service';

/**
 * Opaque, unguessable QR token (not a structured/derivable string like the
 * legacy `T{restaurantId}T{uniqid}` pattern flagged in
 * docs/CURRENT_SYSTEM_AUDIT.md §4 — no restaurant/table identifiers are
 * encoded in it, and it carries no signature to verify because none is
 * needed: resolution is always an exact-match DB lookup on a unique index,
 * so guessing one token gives no information about any other).
 */
function generateQrToken(): string {
  return crypto.randomBytes(24).toString('base64url');
}

export async function listTables(restaurantId: string) {
  return prisma.restaurantTable.findMany({ where: { restaurantId }, orderBy: { tableNumber: 'asc' } });
}

export async function createTable(restaurantId: string, data: { tableNumber: string; seats?: number }) {
  await LIMIT_CHECKERS.tables(restaurantId);
  const table = await prisma.restaurantTable.create({
    data: { restaurantId, tableNumber: data.tableNumber, seats: data.seats ?? 4, qrCode: generateQrToken() },
  });
  await publishRealtime({ channel: 'staff', type: 'table_status', restaurantId });
  return table;
}

export async function updateTable(restaurantId: string, tableId: string, data: Partial<{ tableNumber: string; seats: number; status: TableStatus }>) {
  const table = await prisma.restaurantTable.findFirst({ where: { id: tableId, restaurantId } });
  if (!table) throw NotFound('Table not found');
  const updated = await prisma.restaurantTable.update({ where: { id: tableId }, data });
  await publishRealtime({ channel: 'staff', type: 'table_status', restaurantId });
  return updated;
}

export async function deleteTable(restaurantId: string, tableId: string) {
  const table = await prisma.restaurantTable.findFirst({ where: { id: tableId, restaurantId } });
  if (!table) throw NotFound('Table not found');

  const activeOrder = await prisma.order.findFirst({ where: { tableId, status: { notIn: ['COMPLETED', 'CANCELLED'] } } });
  if (activeOrder) throw Conflict('Cannot delete a table with an active order');

  await prisma.restaurantTable.delete({ where: { id: tableId } });
  await publishRealtime({ channel: 'staff', type: 'table_status', restaurantId });
}

export async function resetTable(restaurantId: string, tableId: string, staffId: string) {
  const table = await prisma.restaurantTable.findFirst({ where: { id: tableId, restaurantId } });
  if (!table) throw NotFound('Table not found');

  const updated = await prisma.$transaction(async (tx) => {
    const result = await tx.restaurantTable.update({ where: { id: tableId }, data: { status: 'AVAILABLE' } });
    await tx.tableReset.create({
      data: { tableId, staffId, previousStatus: table.status, newStatus: 'AVAILABLE' },
    });
    await tx.deviceTableLock.updateMany({ where: { tableId, isActive: true }, data: { isActive: false } });
    return result;
  });
  await publishRealtime({ channel: 'staff', type: 'table_status', restaurantId });

  return updated;
}

export async function regenerateQrCode(restaurantId: string, tableId: string) {
  const table = await prisma.restaurantTable.findFirst({ where: { id: tableId, restaurantId } });
  if (!table) throw NotFound('Table not found');
  return prisma.restaurantTable.update({ where: { id: tableId }, data: { qrCode: generateQrToken() } });
}
