import { NextFunction, Request, Response } from 'express';
import { isMaintenanceModeEnabled } from '@/services/systemSettings.service';

/**
 * Real enforcement of the `maintenance_mode` setting — unlike legacy's
 * `SettingsEnforcement::enforceMaintenanceMode()`, which existed but was
 * never actually called from anywhere in the request pipeline (confirmed by
 * grepping the legacy codebase), so toggling it there had zero real effect.
 *
 * `/health` and `/api/v1/auth/*` stay open so a superadmin can still log in
 * to turn maintenance mode back off, and the entire `/api/v1/admin/*`
 * surface stays open (it already requires SUPER_ADMIN via requireStaffAuth +
 * requireSuperAdmin) so the superadmin console keeps working. Everything
 * else — all customer and non-admin staff routes — gets a 503 while it's on.
 */
export async function maintenanceModeGate(req: Request, res: Response, next: NextFunction): Promise<void> {
  if (req.path === '/health' || req.path.startsWith('/api/v1/auth') || req.path.startsWith('/api/v1/admin')) {
    return next();
  }

  const enabled = await isMaintenanceModeEnabled();
  if (!enabled) return next();

  res.status(503).json({
    status: 'FAIL',
    code: 'MAINTENANCE_MODE',
    message: 'The system is temporarily down for maintenance. Please try again shortly.',
  });
}
