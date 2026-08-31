import { CashSessionStatus, CashTransactionType } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { notifyRoles, notifyUser } from '@/modules/notifications/notification.service';
import { BadRequest, Conflict, Forbidden, NotFound } from '@/utils/httpError';
import { publishRealtime } from '@/services/realtime.service';
import {
  CASH_DISCREPANCY_THRESHOLD,
  closeOutcome,
  hasEnoughCashInDrawer,
  isCashDecrement,
  isExpenseCategory,
} from './cash.constants';

export { CASH_DISCREPANCY_THRESHOLD };

const ACTIVE_SESSION: CashSessionStatus[] = [CashSessionStatus.OPEN, CashSessionStatus.AUDITING];

export async function openSession(restaurantId: string, staffId: string, openingBalance: number) {
  const existing = await prisma.cashSession.findFirst({ where: { staffId, status: { in: ACTIVE_SESSION } } });
  if (existing) {
    if (existing.status === 'AUDITING') {
      throw Conflict('Your last session is waiting for a manager to approve the close');
    }
    throw Conflict('You already have an open cash session');
  }

  const session = await prisma.cashSession.create({
    data: { restaurantId, staffId, openingBalance, cashInHand: openingBalance },
  });
  await publishRealtime({ channel: 'staff', type: 'cash_session_updated', restaurantId, userId: staffId });
  return session;
}

export async function getCurrentSession(staffId: string) {
  return prisma.cashSession.findFirst({
    where: { staffId, status: { in: ACTIVE_SESSION } },
    include: { transactions: { orderBy: { createdAt: 'desc' } } },
  });
}

export async function recordTransaction(
  restaurantId: string,
  staffId: string,
  transactionType: CashTransactionType,
  amount: number,
  description?: string,
  referenceNumber?: string,
  category?: string | null,
) {
  const session = await prisma.cashSession.findFirst({ where: { staffId, status: { in: ACTIVE_SESSION } } });
  if (!session) throw BadRequest('No open cash session — open one before recording transactions');
  if (session.status === 'AUDITING') {
    throw Conflict('This till is waiting for a manager to approve the close — no more payments from the drawer until then');
  }

  const isDecrement = isCashDecrement(transactionType);
  if (isDecrement && !hasEnoughCashInDrawer(Number(session.cashInHand), amount)) {
    throw BadRequest(
      `Not enough cash in the drawer (${Number(session.cashInHand).toLocaleString()} RWF) to pay ${amount.toLocaleString()} RWF`,
    );
  }
  if ((transactionType === 'EXPENSE' || transactionType === 'WITHDRAWAL') && (!description || description.trim().length < 3)) {
    throw BadRequest('A reason is required when cash leaves the drawer');
  }
  if (transactionType === 'EXPENSE' && !isExpenseCategory(category)) {
    throw BadRequest('Choose what the expense was for (electricity, water, supplies, …)');
  }

  const note = description?.trim() || null;
  const expenseCategory = transactionType === 'EXPENSE' && isExpenseCategory(category) ? category : null;
  const ledgerNote = expenseCategory ? `[${expenseCategory}] ${note ?? ''}`.trim() : note;

  const transaction = await prisma.$transaction(async (tx) => {
    const created = await tx.cashTransaction.create({
      data: {
        cashSessionId: session.id,
        restaurantId,
        staffId,
        transactionType,
        amount,
        description: ledgerNote,
        referenceNumber: referenceNumber?.trim() || null,
      },
    });
    if (expenseCategory) {
      await tx.$executeRaw`UPDATE cash_transactions SET category = ${expenseCategory} WHERE id = ${created.id}::uuid`;
    }

    const delta = isDecrement ? -amount : amount;
    await tx.cashSession.update({ where: { id: session.id }, data: { cashInHand: { increment: delta } } });

    // Cash leaving the drawer (withdrawal/expense/adjustment/refund) is the
    // one class of cash-session action a dishonest staff member could use
    // to remove real money while still reconciling clean at session close —
    // it must land on the audit trail, the same place every other
    // money-affecting action does, not just the cash-session transaction
    // list nobody outside that staff member's own history reviews.
    if (isDecrement) {
      await tx.auditTrail.create({
        data: {
          restaurantId,
          staffId,
          actionType: `cash_${transactionType.toLowerCase()}`,
          tableName: 'cash_transactions',
          recordId: created.id,
          reason: expenseCategory ? `[${expenseCategory}] ${note || amount}` : note || `${transactionType} of ${amount}`,
        },
      });
    }

    return created;
  });
  await publishRealtime({ channel: 'staff', type: 'cash_session_updated', restaurantId, userId: staffId });
  return transaction;
}

async function finalizeClose(
  restaurantId: string,
  sessionId: string,
  closedById: string,
  cashInHand: number,
  counted: number,
) {
  const { expectedBalance, variance, status } = closeOutcome(cashInHand, counted);
  const updated = await prisma.cashSession.update({
    where: { id: sessionId },
    data: { closingBalance: counted, expectedBalance, variance, status, closedAt: new Date(), closedById },
  });
  await prisma.auditTrail.create({
    data: {
      restaurantId,
      staffId: closedById,
      actionType: status === 'DISCREPANCY' ? 'cash_close_discrepancy' : 'cash_close',
      tableName: 'cash_sessions',
      recordId: sessionId,
      reason: `Counted ${counted.toLocaleString()} RWF, expected ${expectedBalance.toLocaleString()} RWF`,
      oldValue: String(expectedBalance),
      newValue: String(counted),
    },
  });
  await publishRealtime({ channel: 'staff', type: 'cash_session_updated', restaurantId });
  return updated;
}

/**
 * Cashiers count the drawer and queue the close. Only staff with
 * `approve_actions` (manager/admin) may actually close it. Admin operating
 * the till themselves is the confirming authority, so they finalize now.
 */
export async function requestClose(
  restaurantId: string,
  sessionId: string,
  staffId: string,
  closingBalance: number,
  canFinalize: boolean,
) {
  const session = await prisma.cashSession.findFirst({ where: { id: sessionId, restaurantId } });
  if (!session) throw NotFound('Cash session not found');
  if (session.staffId !== staffId) throw Forbidden('You can only close your own cash session');
  if (session.status === 'AUDITING') throw Conflict('This close is already waiting for a manager');
  if (session.status !== 'OPEN') throw Conflict('This cash session is not open');

  if (canFinalize) {
    return finalizeClose(restaurantId, sessionId, staffId, Number(session.cashInHand), closingBalance);
  }

  const expectedBalance = Number(session.cashInHand);
  const variance = closingBalance - expectedBalance;
  const cashier = await prisma.staffUser.findUnique({ where: { id: staffId }, select: { fullName: true } });
  const cashierName = cashier?.fullName ?? 'Cashier';

  const updated = await prisma.cashSession.update({
    where: { id: sessionId },
    data: { closingBalance, expectedBalance, variance, status: 'AUDITING' },
  });

  await prisma.auditTrail.create({
    data: {
      restaurantId,
      staffId,
      actionType: 'request_cash_close',
      tableName: 'cash_sessions',
      recordId: sessionId,
      reason: `Counted ${closingBalance.toLocaleString()} RWF, expected ${expectedBalance.toLocaleString()} RWF`,
      requiresApproval: true,
      status: 'PENDING',
    },
  });

  await notifyRoles(
    restaurantId,
    ['MANAGER', 'ADMIN'],
    'approval_needed',
    'Cash close needs approval',
    `${cashierName} counted ${closingBalance.toLocaleString()} RWF (system ${expectedBalance.toLocaleString()} RWF)`,
    {
      sessionId,
      kind: 'cash_close',
      staffName: cashierName,
      counted: closingBalance,
      expected: expectedBalance,
      variance,
    },
  );
  await publishRealtime({ channel: 'staff', type: 'cash_session_updated', restaurantId, userId: staffId });
  await publishRealtime({ channel: 'staff', type: 'approval_needed', restaurantId });
  return updated;
}

export async function approveClose(restaurantId: string, sessionId: string, managerId: string) {
  const session = await prisma.cashSession.findFirst({ where: { id: sessionId, restaurantId, status: 'AUDITING' } });
  if (!session) throw NotFound('Cash close waiting for approval was not found');
  if (session.staffId === managerId) throw Conflict('You cannot approve your own cash close — this needs a second person');
  if (session.closingBalance == null) throw Conflict('This session has no counted total to approve');

  const updated = await finalizeClose(
    restaurantId,
    sessionId,
    managerId,
    Number(session.expectedBalance ?? session.cashInHand),
    Number(session.closingBalance),
  );

  await notifyUser(
    restaurantId,
    session.staffId,
    'approval_resolved',
    'Cash close approved',
    `Your cash session was closed at ${Number(session.closingBalance).toLocaleString()} RWF`,
    {
      sessionId,
      kind: 'cash_close',
      decision: 'approved',
      counted: Number(session.closingBalance),
      expected: Number(session.expectedBalance ?? session.cashInHand),
    },
  );
  await publishRealtime({ channel: 'staff', type: 'approval_resolved', restaurantId });
  return updated;
}

export async function rejectClose(restaurantId: string, sessionId: string, managerId: string) {
  const session = await prisma.cashSession.findFirst({ where: { id: sessionId, restaurantId, status: 'AUDITING' } });
  if (!session) throw NotFound('Cash close waiting for approval was not found');
  if (session.staffId === managerId) throw Conflict('You cannot reject your own cash close');

  const updated = await prisma.cashSession.update({
    where: { id: sessionId },
    data: { status: 'OPEN', closingBalance: null, expectedBalance: null, variance: 0 },
  });

  await prisma.auditTrail.create({
    data: {
      restaurantId,
      staffId: managerId,
      actionType: 'reject_cash_close',
      tableName: 'cash_sessions',
      recordId: sessionId,
      reason: 'Cash close sent back to cashier',
    },
  });

  await notifyUser(
    restaurantId,
    session.staffId,
    'approval_resolved',
    'Cash close rejected',
    'A manager sent your cash close back — recount the drawer and submit again',
    {
      sessionId,
      kind: 'cash_close',
      decision: 'rejected',
      counted: Number(session.closingBalance ?? 0),
      expected: Number(session.expectedBalance ?? session.cashInHand),
    },
  );
  await publishRealtime({ channel: 'staff', type: 'cash_session_updated', restaurantId, userId: session.staffId });
  await publishRealtime({ channel: 'staff', type: 'approval_resolved', restaurantId });
  return updated;
}

export async function listPendingCloses(restaurantId: string) {
  const sessions = await prisma.cashSession.findMany({
    where: { restaurantId, status: 'AUDITING' },
    include: { staff: { select: { id: true, fullName: true } } },
    orderBy: { openedAt: 'desc' },
  });
  const staffIds = sessions.map((s) => s.staffId);
  const onShiftStaffIds = staffIds.length
    ? new Set(
        (
          await prisma.staffShift.findMany({
            where: { staffId: { in: staffIds }, clockOut: null, status: 'ACTIVE' },
            select: { staffId: true },
          })
        ).map((s) => s.staffId),
      )
    : new Set<string>();

  return sessions.map((s) => {
    const counted = Number(s.closingBalance ?? 0);
    const expected = Number(s.expectedBalance ?? s.cashInHand);
    const variance = Number(s.variance);
    const sign = variance > 0 ? '+' : '';
    return {
      id: s.id,
      kind: 'CASH_CLOSE' as const,
      orderId: null,
      adjustmentType: 'CASH_CLOSE' as const,
      amount: counted,
      reason: `Counted ${counted.toLocaleString()} RWF — system ${expected.toLocaleString()} RWF (${sign}${variance.toLocaleString()} RWF)`,
      status: 'PENDING' as const,
      requestedById: s.staffId,
      requestedByName: s.staff.fullName,
      requestedByOnShift: onShiftStaffIds.has(s.staffId),
      requestedByActiveOrders: [],
      createdAt: s.openedAt,
      order: null,
      cashSession: {
        openingBalance: Number(s.openingBalance),
        expectedBalance: expected,
        closingBalance: counted,
        variance,
        openedAt: s.openedAt,
      },
    };
  });
}

export async function getHistory(restaurantId: string, staffId: string) {
  return prisma.cashSession.findMany({
    where: { restaurantId, staffId, status: { in: ['CLOSED', 'DISCREPANCY'] } },
    orderBy: { openedAt: 'desc' },
    take: 50,
  });
}
