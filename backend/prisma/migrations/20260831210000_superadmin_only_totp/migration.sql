-- 2FA is superadmin-only. Drop leftover authenticator secrets from restaurant staff.
UPDATE "staff_users"
SET "totp_enabled" = false, "totp_secret" = NULL
WHERE "role" <> 'SUPER_ADMIN';
