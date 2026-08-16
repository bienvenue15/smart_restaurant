import crypto from 'node:crypto';
import { Request } from 'express';

/**
 * Secondary anti-abuse signal only — NOT the security boundary. The legacy
 * app relied on this fingerprint alone to bind a browser session to a
 * table (docs/SECURITY_AUDIT.md #9: spoofable behind shared IP/UA). Here
 * it pairs with a signed session token (see modules/auth/jwt.ts
 * signCustomerSessionToken) which is the actual authority.
 */
export function computeDeviceFingerprint(req: Request): string {
  const ip = getClientIp(req);
  const userAgent = req.headers['user-agent'] ?? '';
  const acceptLanguage = req.headers['accept-language'] ?? '';
  const raw = `${ip}|${userAgent}|${acceptLanguage}`;
  return crypto.createHash('sha256').update(raw).digest('hex');
}

function getClientIp(req: Request): string {
  const forwarded = req.headers['x-forwarded-for'];
  if (typeof forwarded === 'string' && forwarded.length > 0) {
    return forwarded.split(',')[0]!.trim();
  }
  return req.socket.remoteAddress ?? 'unknown';
}
