import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import jwt from 'jsonwebtoken';
import bcrypt from 'bcryptjs';
import express from 'express';
import request from 'supertest';
import { Secret, TOTP } from 'otpauth';
import { StaffRole } from '@prisma/client';
import { config } from '@/config/env';
import { prisma } from '@/config/prisma';
import {
  signCustomerSessionToken,
  signStaffAccessToken,
  signStaffRefreshToken,
  signTwoFactorPendingToken,
  verifyStaffAccessToken,
} from '@/modules/auth/jwt';
import { encryptTotpSecret, generateTotpSecret, verifyTotpCode } from '@/utils/totp';

vi.mock('pino-http', () => ({
  default: () => (_req: unknown, _res: unknown, next: () => void) => next(),
}));

vi.mock('@/middleware/rateLimit', () => ({
  loginLimiter: (_req: unknown, _res: unknown, next: () => void) => next(),
  passwordResetLimiter: (_req: unknown, _res: unknown, next: () => void) => next(),
  publicFormLimiter: (_req: unknown, _res: unknown, next: () => void) => next(),
}));

vi.mock('@/middleware/permission', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/middleware/permission')>();
  return { ...actual, listPermissionsForRole: vi.fn(async () => []) };
});

vi.mock('@/modules/subscriptions/subscription.service', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/modules/subscriptions/subscription.service')>();
  return { ...actual, getEntitlements: vi.fn(async () => null) };
});

vi.mock('@/config/prisma', () => ({
  prisma: {
    staffUser: {
      findUnique: vi.fn(),
      findFirst: vi.fn(),
      update: vi.fn(),
    },
    staffActivityLog: { create: vi.fn() },
    restaurant: { findUnique: vi.fn() },
    passwordResetToken: {
      deleteMany: vi.fn(),
      create: vi.fn(),
      findUnique: vi.fn(),
      update: vi.fn(),
    },
    rolePermission: { findMany: vi.fn(), findUnique: vi.fn() },
  },
}));

type StaffRow = {
  id: string;
  username: string;
  passwordHash: string;
  fullName: string;
  role: StaffRole;
  restaurantId: string | null;
  isActive: boolean;
  canHandleCash: boolean;
  totpEnabled: boolean;
  totpSecret: string | null;
  email: string | null;
  phone: string | null;
};

const PASSWORD = 'correct-horse-battery';
let passwordHash: string;
let app: express.Express;

function totpCode(username: string, secret: string) {
  return new TOTP({
    issuer: 'Smart Restaurant',
    label: username,
    algorithm: 'SHA1',
    digits: 6,
    period: 30,
    secret: Secret.fromBase32(secret),
  }).generate();
}

function unsignedJwt(payload: object) {
  const header = Buffer.from(JSON.stringify({ alg: 'none', typ: 'JWT' })).toString('base64url');
  const body = Buffer.from(JSON.stringify(payload)).toString('base64url');
  return `${header}.${body}.`;
}

function cookieHeader(res: request.Response, name: string) {
  const raw = res.headers['set-cookie'];
  const list = Array.isArray(raw) ? raw : raw ? [raw] : [];
  return list.find((row) => row.startsWith(`${name}=`));
}

function staffFixture(overrides: Partial<StaffRow> = {}): StaffRow {
  return {
    id: 'staff-waiter',
    username: 'waiter1',
    passwordHash,
    fullName: 'Waiter One',
    role: StaffRole.WAITER,
    restaurantId: 'rest-1',
    isActive: true,
    canHandleCash: false,
    totpEnabled: false,
    totpSecret: null,
    email: null,
    phone: null,
    ...overrides,
  };
}

function installStaff(row: StaffRow) {
  vi.mocked(prisma.staffUser.findUnique).mockImplementation(async ({ where }) => {
    if ('username' in where && where.username === row.username) return row as never;
    if ('id' in where && where.id === row.id) return row as never;
    return null;
  });
  vi.mocked(prisma.staffUser.update).mockResolvedValue(row as never);
  vi.mocked(prisma.staffActivityLog.create).mockResolvedValue({} as never);
  vi.mocked(prisma.restaurant.findUnique).mockResolvedValue({
    name: 'Demo Kitchen',
    logoUrl: null,
    primaryColor: null,
    secondaryColor: null,
  } as never);
}

beforeAll(async () => {
  passwordHash = await bcrypt.hash(PASSWORD, 4);
  const { createApp } = await import('@/app');
  app = createApp();
});

beforeEach(() => {
  vi.mocked(prisma.staffUser.findUnique).mockReset();
  vi.mocked(prisma.staffUser.findFirst).mockReset();
  vi.mocked(prisma.staffUser.update).mockReset();
  vi.mocked(prisma.staffActivityLog.create).mockReset();
  vi.mocked(prisma.restaurant.findUnique).mockReset();
});

describe('JWT confusion / forgery', () => {
  it('rejects alg=none tokens as a staff session', () => {
    const forged = unsignedJwt({
      sub: 'sa-1',
      role: 'SUPER_ADMIN',
      restaurantId: null,
      typ: 'access',
    });
    expect(() => verifyStaffAccessToken(forged)).toThrow();
  });

  it('rejects a token signed with the wrong secret', () => {
    const forged = jwt.sign(
      { sub: 'sa-1', role: 'SUPER_ADMIN', restaurantId: null, typ: 'access' },
      'attacker-secret-that-is-at-least-32-chars!',
      { expiresIn: '15m' },
    );
    expect(() => verifyStaffAccessToken(forged)).toThrow();
  });

  it('rejects a refresh token used as a staff session', () => {
    const refresh = signStaffRefreshToken({ sub: 'staff-waiter' });
    expect(() => verifyStaffAccessToken(refresh)).toThrow();
  });

  it('rejects a customer table-session token used as a staff session', () => {
    const customer = signCustomerSessionToken({ sub: 'lock-1', restaurantId: 'rest-1', tableId: 'table-1' });
    expect(() => verifyStaffAccessToken(customer)).toThrow(/Not a staff access token/);
  });

  it('rejects a waiter token whose role claim was edited to SUPER_ADMIN', () => {
    const waiter = signStaffAccessToken({
      sub: 'staff-waiter',
      role: StaffRole.WAITER,
      restaurantId: 'rest-1',
    });
    const [header, payload, sig] = waiter.split('.');
    const claims = JSON.parse(Buffer.from(payload!, 'base64url').toString());
    claims.role = 'SUPER_ADMIN';
    claims.restaurantId = null;
    const tampered = `${header}.${Buffer.from(JSON.stringify(claims)).toString('base64url')}.${sig}`;
    expect(() => verifyStaffAccessToken(tampered)).toThrow();
  });
});

describe('unauthenticated and stolen-session attacks on 2FA endpoints', () => {
  it('rejects 2FA setup/status/enable/disable with no Authorization header', async () => {
    for (const req of [
      request(app).get('/api/v1/auth/2fa'),
      request(app).post('/api/v1/auth/2fa/setup'),
      request(app).post('/api/v1/auth/2fa/cancel'),
      request(app).post('/api/v1/auth/2fa/enable').send({ code: '123456' }),
      request(app).post('/api/v1/auth/2fa/disable').send({ password: PASSWORD, code: '123456' }),
    ]) {
      const res = await req;
      expect(res.status).toBe(401);
      expect(prisma.staffUser.update).not.toHaveBeenCalled();
    }
  });

  it('rejects a 2FA pending token used as a Bearer session on /auth/2fa', async () => {
    const pending = signTwoFactorPendingToken({ sub: 'sa-1' });
    const res = await request(app).get('/api/v1/auth/2fa').set('Authorization', `Bearer ${pending}`);
    expect(res.status).toBe(401);
  });

  it('rejects a customer session token used as Bearer on /auth/2fa', async () => {
    const customer = signCustomerSessionToken({ sub: 'lock-1', restaurantId: 'rest-1', tableId: 'table-1' });
    const res = await request(app).get('/api/v1/auth/2fa').set('Authorization', `Bearer ${customer}`);
    expect(res.status).toBe(401);
  });
});

describe('privilege escalation: restaurant staff cannot manage 2FA', () => {
  it.each([StaffRole.WAITER, StaffRole.ADMIN, StaffRole.MANAGER, StaffRole.KITCHEN, StaffRole.CASHIER])(
    'forbids %s from reading or changing 2FA',
    async (role) => {
      const token = signStaffAccessToken({ sub: 'staff-1', role, restaurantId: 'rest-1' });
      const headers = { Authorization: `Bearer ${token}` };

      const status = await request(app).get('/api/v1/auth/2fa').set(headers);
      const setup = await request(app).post('/api/v1/auth/2fa/setup').set(headers);
      const enable = await request(app).post('/api/v1/auth/2fa/enable').set(headers).send({ code: '123456' });
      const disable = await request(app)
        .post('/api/v1/auth/2fa/disable')
        .set(headers)
        .send({ password: PASSWORD, code: '123456' });

      expect(status.status).toBe(403);
      expect(setup.status).toBe(403);
      expect(enable.status).toBe(403);
      expect(disable.status).toBe(403);
      expect(prisma.staffUser.update).not.toHaveBeenCalled();
    },
  );
});

describe('login bypass / leftover staff TOTP', () => {
  it('does not distinguish unknown users from bad passwords', async () => {
    vi.mocked(prisma.staffUser.findUnique).mockResolvedValue(null);
    const unknown = await request(app).post('/api/v1/auth/login').send({ username: 'nobody', password: PASSWORD });
    installStaff(staffFixture());
    const badPass = await request(app).post('/api/v1/auth/login').send({ username: 'waiter1', password: 'wrong-password' });

    expect(unknown.status).toBe(401);
    expect(badPass.status).toBe(401);
    expect(unknown.body.message).toBe(badPass.body.message);
    expect(unknown.body.message).toMatch(/Invalid username or password/);
  });

  it('issues a normal session for restaurant staff even if leftover totp flags are set', async () => {
    const leftoverSecret = encryptTotpSecret(generateTotpSecret());
    installStaff(
      staffFixture({
        totpEnabled: true,
        totpSecret: leftoverSecret,
        role: StaffRole.ADMIN,
        id: 'admin-1',
        username: 'owner1',
      }),
    );

    const res = await request(app).post('/api/v1/auth/login').send({ username: 'owner1', password: PASSWORD });
    expect(res.status).toBe(200);
    expect(res.body.data.requiresTwoFactor).toBe(false);
    expect(res.body.data.accessToken).toBeTruthy();
    expect(res.body.data.pendingToken).toBeUndefined();
    expect(cookieHeader(res, 'sr_staff_refresh')).toBeTruthy();
  });

  it('accepts the staff email as the login identifier', async () => {
    const row = staffFixture({ email: 'waiter@example.com' });
    installStaff(row);
    vi.mocked(prisma.staffUser.findFirst).mockResolvedValue(row as never);

    const res = await request(app)
      .post('/api/v1/auth/login')
      .send({ username: 'Waiter@Example.com', password: PASSWORD });
    expect(res.status).toBe(200);
    expect(res.body.data.accessToken).toBeTruthy();
  });

  it('does not issue tokens after password-only login when superadmin 2FA is on', async () => {
    const secret = generateTotpSecret();
    installStaff(
      staffFixture({
        id: 'sa-1',
        username: 'superadmin',
        role: StaffRole.SUPER_ADMIN,
        restaurantId: null,
        totpEnabled: true,
        totpSecret: encryptTotpSecret(secret),
      }),
    );

    const res = await request(app).post('/api/v1/auth/login').send({ username: 'superadmin', password: PASSWORD });
    expect(res.status).toBe(200);
    expect(res.body.data.requiresTwoFactor).toBe(true);
    expect(res.body.data.pendingToken).toBeTruthy();
    expect(res.body.data.accessToken).toBeUndefined();
    expect(res.body.data.refreshToken).toBeUndefined();
    expect(cookieHeader(res, 'sr_staff_refresh')).toBeUndefined();
  });

  it('blocks login for a deactivated superadmin even with the right password', async () => {
    installStaff(
      staffFixture({
        id: 'sa-1',
        username: 'superadmin',
        role: StaffRole.SUPER_ADMIN,
        restaurantId: null,
        isActive: false,
      }),
    );
    const res = await request(app).post('/api/v1/auth/login').send({ username: 'superadmin', password: PASSWORD });
    expect(res.status).toBe(401);
    expect(res.body.data?.accessToken).toBeUndefined();
  });
});

describe('TOTP guessing, replay, and stolen pending tokens', () => {
  const secret = generateTotpSecret();
  const superadmin = () =>
    staffFixture({
      id: 'sa-1',
      username: 'superadmin',
      role: StaffRole.SUPER_ADMIN,
      restaurantId: null,
      totpEnabled: true,
      totpSecret: encryptTotpSecret(secret),
    });

  it('rejects an expired 2FA pending token', async () => {
    installStaff(superadmin());
    const expired = jwt.sign({ sub: 'sa-1', typ: '2fa' }, config.jwtAccessSecret, { expiresIn: '-1s' });
    const res = await request(app)
      .post('/api/v1/auth/login/2fa')
      .send({ pendingToken: expired, code: totpCode('superadmin', secret) });
    expect(res.status).toBe(401);
    expect(res.body.message).toMatch(/expired/i);
    expect(cookieHeader(res, 'sr_staff_refresh')).toBeUndefined();
  });

  it('rejects an access token submitted as a 2FA pending token', async () => {
    installStaff(superadmin());
    const access = signStaffAccessToken({ sub: 'sa-1', role: StaffRole.SUPER_ADMIN, restaurantId: null });
    const res = await request(app)
      .post('/api/v1/auth/login/2fa')
      .send({ pendingToken: access, code: totpCode('superadmin', secret) });
    expect(res.status).toBe(401);
    expect(cookieHeader(res, 'sr_staff_refresh')).toBeUndefined();
  });

  it('rejects a stolen pending token after the account is deactivated', async () => {
    const pending = signTwoFactorPendingToken({ sub: 'sa-1' });
    installStaff({ ...superadmin(), isActive: false });
    const res = await request(app)
      .post('/api/v1/auth/login/2fa')
      .send({ pendingToken: pending, code: totpCode('superadmin', secret) });
    expect(res.status).toBe(401);
    expect(cookieHeader(res, 'sr_staff_refresh')).toBeUndefined();
  });

  it('rejects a wrong authenticator code and does not set a session cookie', async () => {
    installStaff(superadmin());
    const pending = signTwoFactorPendingToken({ sub: 'sa-1' });
    const res = await request(app).post('/api/v1/auth/login/2fa').send({ pendingToken: pending, code: '000000' });
    expect(res.status).toBe(401);
    expect(res.body.message).toMatch(/Invalid authenticator code/);
    expect(cookieHeader(res, 'sr_staff_refresh')).toBeUndefined();
  });

  it('rejects a TOTP generated from a different secret', async () => {
    installStaff(superadmin());
    const pending = signTwoFactorPendingToken({ sub: 'sa-1' });
    const attackerSecret = generateTotpSecret();
    const res = await request(app)
      .post('/api/v1/auth/login/2fa')
      .send({ pendingToken: pending, code: totpCode('superadmin', attackerSecret) });
    expect(res.status).toBe(401);
    expect(cookieHeader(res, 'sr_staff_refresh')).toBeUndefined();
  });

  it('rejects SQL/script payloads in the authenticator code field', async () => {
    installStaff(superadmin());
    const pending = signTwoFactorPendingToken({ sub: 'sa-1' });
    for (const code of ["' OR 1=1 --", '<script>1</script>', '1234567', 'abcdef', '']) {
      const res = await request(app).post('/api/v1/auth/login/2fa').send({ pendingToken: pending, code });
      expect(res.status).toBe(400);
      expect(cookieHeader(res, 'sr_staff_refresh')).toBeUndefined();
    }
  });

  it('does not issue a session when the stored TOTP ciphertext was tampered with', async () => {
    const row = superadmin();
    row.totpSecret = `${row.totpSecret}aa`;
    installStaff(row);
    const pending = signTwoFactorPendingToken({ sub: 'sa-1' });
    const res = await request(app)
      .post('/api/v1/auth/login/2fa')
      .send({ pendingToken: pending, code: totpCode('superadmin', secret) });
    expect(res.body.status).not.toBe('OK');
    expect(res.body.data?.accessToken).toBeUndefined();
    expect(cookieHeader(res, 'sr_staff_refresh')).toBeUndefined();
  });

  it('allows a stolen pending token only with the current valid code (happy path control)', async () => {
    installStaff(superadmin());
    const pending = signTwoFactorPendingToken({ sub: 'sa-1' });
    const code = totpCode('superadmin', secret);
    expect(verifyTotpCode('superadmin', secret, code)).toBe(true);

    const res = await request(app).post('/api/v1/auth/login/2fa').send({ pendingToken: pending, code });
    expect(res.status).toBe(200);
    expect(res.body.data.accessToken).toBeTruthy();
    expect(cookieHeader(res, 'sr_staff_refresh')).toMatch(/HttpOnly/i);
  });
});

describe('refresh cookie', () => {
  it('returns an empty success when no refresh cookie is present', async () => {
    const res = await request(app).post('/api/v1/auth/refresh');
    expect(res.status).toBe(204);
  });

  it('still rejects an invalid refresh cookie', async () => {
    const res = await request(app).post('/api/v1/auth/refresh').set('Cookie', 'sr_staff_refresh=not-a-jwt');
    expect(res.status).toBe(401);
  });
});

describe('login brute-force limiter', () => {
  it('returns 429 after 20 failed attempts from the same client', async () => {
    const { loginLimiter } = await vi.importActual<typeof import('@/middleware/rateLimit')>('@/middleware/rateLimit');
    const isolated = express();
    isolated.use(express.json());
    isolated.use('/login', loginLimiter);
    isolated.post('/login', (_req, res) => res.status(401).json({ status: 'FAIL', message: 'Invalid username or password' }));

    for (let i = 0; i < 20; i += 1) {
      const res = await request(isolated).post('/login').send({ username: 'x', password: 'bad-password' });
      expect(res.status).toBe(401);
    }
    const blocked = await request(isolated).post('/login').send({ username: 'x', password: 'bad-password' });
    expect(blocked.status).toBe(429);
    expect(blocked.body.code).toBe('RATE_LIMITED');
  });
});
