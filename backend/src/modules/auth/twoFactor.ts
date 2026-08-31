import { StaffRole } from '@prisma/client';

/** 2FA is a superadmin-only account lock. Restaurant staff never get a TOTP challenge. */
export function shouldChallengeTwoFactor(staff: {
  role: StaffRole;
  totpEnabled: boolean;
  totpSecret: string | null;
}): boolean {
  return staff.role === StaffRole.SUPER_ADMIN && staff.totpEnabled && Boolean(staff.totpSecret);
}
