import { NextFunction, Request, Response } from 'express';
import { prisma } from '@/config/prisma';
import { Forbidden, Unauthorized } from '@/utils/httpError';

/**
 * Single, DB-backed RBAC check — the legacy app had three overlapping
 * mechanisms (DB-driven permission codes, hardcoded role arrays scattered
 * through controllers/views, and shift-gating conflated with permissions)
 * that drifted out of sync with each other (docs/CURRENT_SYSTEM_AUDIT.md §3).
 * This middleware is the ONLY way a route may authorize an action — there
 * is no inline `if (role === 'admin')` escape hatch anywhere else in the
 * codebase. A route that forgets to call this simply has no protection,
 * which is a visible, greppable gap rather than a silent one.
 */
export function requirePermission(code: string) {
  return async (req: Request, _res: Response, next: NextFunction): Promise<void> => {
    if (!req.staff) return next(Unauthorized());

    const grant = await prisma.rolePermission.findUnique({
      where: { role_permissionCode: { role: req.staff.role, permissionCode: code } },
    });

    if (!grant) return next(Forbidden(`Missing permission: ${code}`));
    next();
  };
}

export function requireAnyPermission(codes: string[]) {
  return async (req: Request, _res: Response, next: NextFunction): Promise<void> => {
    if (!req.staff) return next(Unauthorized());

    const grants = await prisma.rolePermission.findMany({
      where: { role: req.staff.role, permissionCode: { in: codes } },
      select: { permissionCode: true },
    });

    if (grants.length === 0) return next(Forbidden(`Missing one of permissions: ${codes.join(', ')}`));
    next();
  };
}
