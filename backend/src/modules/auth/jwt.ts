import jwt from 'jsonwebtoken';
import { config } from '@/config/env';
import { StaffRole } from '@prisma/client';

export interface StaffAccessTokenPayload {
  sub: string; // staff user id
  role: StaffRole;
  restaurantId: string | null; // null only for SUPER_ADMIN
  typ?: 'access' | '2fa';
}

export interface CustomerSessionTokenPayload {
  sub: string; // device-table-lock id
  restaurantId: string;
  tableId: string;
}

export function signStaffAccessToken(payload: StaffAccessTokenPayload): string {
  const options: jwt.SignOptions = { expiresIn: config.jwtAccessTtl as jwt.SignOptions['expiresIn'] };
  return jwt.sign({ ...payload, typ: 'access' }, config.jwtAccessSecret, options);
}

/** Short-lived token issued after password check when 2FA is enabled. Cannot be used as a session. */
export function signTwoFactorPendingToken(payload: { sub: string }): string {
  return jwt.sign({ sub: payload.sub, typ: '2fa' }, config.jwtAccessSecret, { expiresIn: '5m' });
}

export function verifyTwoFactorPendingToken(token: string): { sub: string } {
  const payload = jwt.verify(token, config.jwtAccessSecret) as StaffAccessTokenPayload;
  if (payload.typ !== '2fa' || !payload.sub) {
    throw new Error('Not a 2FA pending token');
  }
  return { sub: payload.sub };
}

export function signStaffRefreshToken(payload: { sub: string }): string {
  const options: jwt.SignOptions = { expiresIn: config.jwtRefreshTtl as jwt.SignOptions['expiresIn'] };
  return jwt.sign(payload, config.jwtRefreshSecret, options);
}

export function verifyStaffAccessToken(token: string): StaffAccessTokenPayload {
  const payload = jwt.verify(token, config.jwtAccessSecret) as StaffAccessTokenPayload;
  if (payload.typ === '2fa') {
    throw new Error('2FA pending token cannot be used as a session');
  }
  return payload;
}

export function verifyStaffRefreshToken(token: string): { sub: string } {
  return jwt.verify(token, config.jwtRefreshSecret) as { sub: string };
}

/**
 * Anonymous customer table-session token. Replaces the legacy
 * device-fingerprint-only trust model (docs/SECURITY_AUDIT.md #9) — the
 * fingerprint remains a secondary anti-abuse signal, but the signed token
 * is now the actual authority for "this browser owns this table session."
 */
export function signCustomerSessionToken(payload: CustomerSessionTokenPayload): string {
  return jwt.sign(payload, config.jwtAccessSecret, { expiresIn: '2h' });
}

export function verifyCustomerSessionToken(token: string): CustomerSessionTokenPayload {
  return jwt.verify(token, config.jwtAccessSecret) as CustomerSessionTokenPayload;
}
