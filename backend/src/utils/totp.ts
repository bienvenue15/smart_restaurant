import crypto from 'node:crypto';
import { Secret, TOTP } from 'otpauth';
import QRCode from 'qrcode';
import { config } from '@/config/env';

function encryptionKey(): Buffer {
  return crypto.createHash('sha256').update(config.jwtAccessSecret).digest();
}

export function encryptTotpSecret(plain: string): string {
  const iv = crypto.randomBytes(12);
  const cipher = crypto.createCipheriv('aes-256-gcm', encryptionKey(), iv);
  const encrypted = Buffer.concat([cipher.update(plain, 'utf8'), cipher.final()]);
  const tag = cipher.getAuthTag();
  return Buffer.concat([iv, tag, encrypted]).toString('base64url');
}

export function decryptTotpSecret(payload: string): string {
  const buf = Buffer.from(payload, 'base64url');
  const iv = buf.subarray(0, 12);
  const tag = buf.subarray(12, 28);
  const encrypted = buf.subarray(28);
  const decipher = crypto.createDecipheriv('aes-256-gcm', encryptionKey(), iv);
  decipher.setAuthTag(tag);
  return Buffer.concat([decipher.update(encrypted), decipher.final()]).toString('utf8');
}

export function generateTotpSecret(): string {
  return new Secret({ size: 20 }).base32;
}

function totpFor(username: string, secret: string): TOTP {
  return new TOTP({
    issuer: 'Smart Restaurant',
    label: username,
    algorithm: 'SHA1',
    digits: 6,
    period: 30,
    secret: Secret.fromBase32(secret),
  });
}

export function verifyTotpCode(username: string, secret: string, code: string): boolean {
  const delta = totpFor(username, secret).validate({ token: code.replace(/\s/g, ''), window: 1 });
  return delta !== null;
}

export async function totpSetupPayload(username: string, secret: string) {
  const totp = totpFor(username, secret);
  const otpauthUrl = totp.toString();
  const qrDataUrl = await QRCode.toDataURL(otpauthUrl, { margin: 1, width: 220 });
  return { secret, otpauthUrl, qrDataUrl };
}
