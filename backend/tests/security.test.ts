import { describe, expect, it } from 'vitest';
import { Secret, TOTP } from 'otpauth';
import {
  signCustomerSessionToken,
  signStaffAccessToken,
  signTwoFactorPendingToken,
  verifyStaffAccessToken,
  verifyTwoFactorPendingToken,
} from '@/modules/auth/jwt';
import { shouldChallengeTwoFactor } from '@/modules/auth/twoFactor';
import { decryptTotpSecret, encryptTotpSecret, generateTotpSecret, verifyTotpCode } from '@/utils/totp';
import { eventVisibleToCustomer, eventVisibleToStaff } from '@/services/realtime.service';

describe('2FA pending tokens', () => {
  it('cannot be used as a staff session', () => {
    const pending = signTwoFactorPendingToken({ sub: 'staff-1' });
    expect(() => verifyStaffAccessToken(pending)).toThrow(/2FA pending/);
    expect(verifyTwoFactorPendingToken(pending).sub).toBe('staff-1');
  });

  it('access tokens are not accepted as 2FA pending tokens', () => {
    const access = signStaffAccessToken({ sub: 'staff-1', role: 'WAITER', restaurantId: 'r1' });
    expect(verifyStaffAccessToken(access).sub).toBe('staff-1');
    expect(() => verifyTwoFactorPendingToken(access)).toThrow(/Not a 2FA/);
  });

  it('rejects a customer table-session token used as a staff session', () => {
    const customer = signCustomerSessionToken({ sub: 'lock-1', restaurantId: 'rest-a', tableId: 'table-1' });
    expect(() => verifyStaffAccessToken(customer)).toThrow(/Not a staff access token/);
  });
});

describe('2FA eligibility', () => {
  it('challenges only a superadmin with 2FA enabled', () => {
    expect(
      shouldChallengeTwoFactor({ role: 'SUPER_ADMIN', totpEnabled: true, totpSecret: 'enc' }),
    ).toBe(true);
    expect(
      shouldChallengeTwoFactor({ role: 'ADMIN', totpEnabled: true, totpSecret: 'enc' }),
    ).toBe(false);
    expect(
      shouldChallengeTwoFactor({ role: 'SUPER_ADMIN', totpEnabled: false, totpSecret: 'enc' }),
    ).toBe(false);
  });
});

describe('TOTP helpers', () => {
  it('round-trips an encrypted secret and validates the current code', () => {
    const secret = generateTotpSecret();
    const stored = encryptTotpSecret(secret);
    expect(decryptTotpSecret(stored)).toBe(secret);

    const code = new TOTP({
      algorithm: 'SHA1',
      digits: 6,
      period: 30,
      secret: Secret.fromBase32(secret),
    }).generate();
    expect(verifyTotpCode('demo', secret, code)).toBe(true);
    expect(verifyTotpCode('demo', secret, '000000')).toBe(false);
  });
});

describe('tenant / IDOR visibility', () => {
  const waiterA = { id: 'w1', role: 'WAITER', restaurantId: 'rest-a' };
  const waiterB = { id: 'w2', role: 'WAITER', restaurantId: 'rest-b' };
  const superadmin = { id: 'sa', role: 'SUPER_ADMIN', restaurantId: null };

  it('hides another restaurant staff events from a waiter', () => {
    const event = { channel: 'staff' as const, type: 'waiter_call', restaurantId: 'rest-a', at: new Date().toISOString() };
    expect(eventVisibleToStaff(event, waiterA)).toBe(true);
    expect(eventVisibleToStaff(event, waiterB)).toBe(false);
    expect(eventVisibleToStaff(event, superadmin)).toBe(true);
  });

  it('scopes user-targeted events to that staff member only', () => {
    const event = {
      channel: 'staff' as const,
      type: 'order_assignment',
      restaurantId: 'rest-a',
      userId: 'w1',
      at: new Date().toISOString(),
    };
    expect(eventVisibleToStaff(event, waiterA)).toBe(true);
    expect(eventVisibleToStaff(event, { id: 'w9', role: 'WAITER', restaurantId: 'rest-a' })).toBe(false);
  });

  it('scopes customer order events to the signed table session (IDOR guard)', () => {
    const event = {
      channel: 'customer' as const,
      type: 'order_status',
      restaurantId: 'rest-a',
      tableId: 'table-1',
      at: new Date().toISOString(),
    };
    expect(eventVisibleToCustomer(event, { restaurantId: 'rest-a', tableId: 'table-1' })).toBe(true);
    expect(eventVisibleToCustomer(event, { restaurantId: 'rest-a', tableId: 'table-2' })).toBe(false);
    expect(eventVisibleToCustomer(event, { restaurantId: 'rest-b', tableId: 'table-1' })).toBe(false);
    expect(eventVisibleToStaff(event, waiterA)).toBe(false);
  });
});

describe('customer order lookup contract', () => {
  it('always includes restaurantId + tableId from the session, never the client order id alone', async () => {
    const { customerOrderLookupWhere } = await import('@/utils/tenantScope');
    expect(customerOrderLookupWhere({ restaurantId: 'rest-a', tableId: 'table-1' }, 'order-9')).toEqual({
      id: 'order-9',
      restaurantId: 'rest-a',
      tableId: 'table-1',
    });
  });
});
