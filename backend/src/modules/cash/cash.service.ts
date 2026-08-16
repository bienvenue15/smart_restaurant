import { CashTransactionType } from '@prisma/client';
import { prisma } from '@/config/prisma';
import { BadRequest, Conflict, NotFound } from '@/utils/httpError';

/** 1,000 RWF cash-session discrepancy threshold, preserved from legacy (docs/CURRENT_SYSTEM_AUDIT.md §5). */
export const CASH_DISCREPANCY_THRESHOLD = 1_000;

export async function openSession(restaurantId: string, staffId: string, openingBalance: number) {
  const existing = await prisma.cashSession.findFirst({ where: { staffId, status: 'OPEN' } });
  if (existing) throw Conflict('You already have an open cash session');

  return prisma.cashSession.create({
    data: { restaurantId, staffId, openingBalance, cashInHand: openingBalance },
  });
}

export async function getCurrentSession(staffId: string) {
  return prisma.cashSession.findFirst({
    where: { staffId, status: 'OPEN' },
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
) {
  const session = await prisma.cashSession.findFirst({ where: { staffId, status: 'OPEN' } });
  if (!session) throw BadRequest('No open cash session — open one before recording transactions');

  return prisma.$transaction(async (tx) => {
    const transaction = await tx.cashTransaction.create({
      data: { cashSessionId: session.id, restaurantId, staffId, transactionType, amount, description, referenceNumber },
    });

    const delta = ['SALE', 'DEPOSIT'].includes(transactionType) ? amount : -amount;
    await tx.cashSession.update({ where: { id: session.id }, data: { cashInHand: { increment: delta } } });

    return transaction;
  });
}

export async function closeSession(restaurantId: string, sessionId: string, staffId: string, closingBalance: number) {
  const session = await prisma.cashSession.findFirst({ where: { id: sessionId, restaurantId, status: 'OPEN' } });
  if (!session) throw NotFound('Open cash session not found');

  const expectedBalance = Number(session.cashInHand);
  const variance = closingBalance - expectedBalance;
  const status = Math.abs(variance) > CASH_DISCREPANCY_THRESHOLD ? 'DISCREPANCY' : 'CLOSED';

  return prisma.cashSession.update({
    where: { id: sessionId },
    data: { closingBalance, expectedBalance, variance, status, closedAt: new Date(), closedById: staffId },
  });
}

export async function getHistory(restaurantId: string, staffId: string) {
  return prisma.cashSession.findMany({
    where: { restaurantId, staffId, status: { in: ['CLOSED', 'DISCREPANCY', 'AUDITING'] } },
    orderBy: { openedAt: 'desc' },
    take: 50,
  });
}
