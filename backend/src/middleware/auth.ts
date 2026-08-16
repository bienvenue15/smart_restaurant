import { NextFunction, Request, Response } from 'express';
import { StaffRole } from '@prisma/client';
import { verifyStaffAccessToken, verifyCustomerSessionToken } from '@/modules/auth/jwt';
import { Unauthorized } from '@/utils/httpError';

declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace Express {
    interface Request {
      staff?: {
        id: string;
        role: StaffRole;
        restaurantId: string | null;
      };
      customerSession?: {
        deviceTableLockId: string;
        restaurantId: string;
        tableId: string;
      };
    }
  }
}

function extractBearerToken(req: Request): string | null {
  const header = req.headers.authorization;
  if (!header?.startsWith('Bearer ')) return null;
  return header.slice('Bearer '.length);
}

/**
 * Verifies a staff JWT and attaches `req.staff`. The restaurantId claim is
 * the ONLY source of tenant identity for the rest of the request pipeline —
 * never a request body/query param. This directly closes the legacy
 * cross-tenant leakage bug (docs/SECURITY_AUDIT.md #4).
 */
export function requireStaffAuth(req: Request, _res: Response, next: NextFunction): void {
  const token = extractBearerToken(req);
  if (!token) return next(Unauthorized());

  try {
    const payload = verifyStaffAccessToken(token);
    req.staff = { id: payload.sub, role: payload.role, restaurantId: payload.restaurantId };
    next();
  } catch {
    next(Unauthorized('Invalid or expired session'));
  }
}

/**
 * Verifies the signed customer table-session cookie/token and attaches
 * `req.customerSession`. Table/restaurant identity comes only from this
 * verified token, never from a client-supplied restaurant_id/table_id body
 * field (the legacy customer flow at least cross-checked this; we go
 * further and never trust the client value in the first place).
 */
export function requireCustomerSession(req: Request, _res: Response, next: NextFunction): void {
  const token = req.cookies?.['sr_customer_session'] ?? extractBearerToken(req);
  if (!token) return next(Unauthorized('No active table session'));

  try {
    const payload = verifyCustomerSessionToken(token);
    req.customerSession = {
      deviceTableLockId: payload.sub,
      restaurantId: payload.restaurantId,
      tableId: payload.tableId,
    };
    next();
  } catch {
    next(Unauthorized('Table session expired — please scan the QR code again'));
  }
}
