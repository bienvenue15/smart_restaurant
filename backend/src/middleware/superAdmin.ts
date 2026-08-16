import { NextFunction, Request, Response } from 'express';
import { Forbidden, Unauthorized } from '@/utils/httpError';

/**
 * A single DB-role check, no exceptions. The legacy `isSuperAdmin()`
 * accepted the DB role check OR a hardcoded fallback email
 * (`superadmin@restaurant.com`) — a bootstrap backdoor
 * (docs/SECURITY_AUDIT.md #7). There is no equivalent bypass here.
 */
export function requireSuperAdmin(req: Request, _res: Response, next: NextFunction): void {
  if (!req.staff) return next(Unauthorized());
  if (req.staff.role !== 'SUPER_ADMIN') return next(Forbidden('Super admin access required'));
  next();
}
