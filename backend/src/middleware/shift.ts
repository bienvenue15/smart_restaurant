import { NextFunction, Request, Response } from 'express';
import { prisma } from '@/config/prisma';
import { Forbidden, Unauthorized } from '@/utils/httpError';

/**
 * Legacy business rule: nearly every mutating staff action requires the
 * staff member to be currently clocked in (docs/CURRENT_SYSTEM_AUDIT.md §5).
 * This is deliberately a SEPARATE middleware from requirePermission — the
 * legacy code conflated "can this role do X" with "is this person on shift
 * right now" in places, which made the two concepts hard to reason about
 * independently. Compose both explicitly on routes that need them.
 *
 * Also fixes a legacy bug: the PHP version checked
 * `DATE(clock_in) = CURDATE()`, which loses track of shifts that start
 * before midnight and end after — this version checks only `clockOut IS NULL`.
 */
export async function requireActiveShift(req: Request, _res: Response, next: NextFunction): Promise<void> {
  if (!req.staff) return next(Unauthorized());

  const openShift = await prisma.staffShift.findFirst({
    where: { staffId: req.staff.id, clockOut: null, status: 'ACTIVE' },
  });

  if (!openShift) return next(Forbidden('You must be clocked in to perform this action'));
  next();
}
