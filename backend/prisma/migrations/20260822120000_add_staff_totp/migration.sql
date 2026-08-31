-- Staff TOTP 2FA. totp_secret is AES-GCM encrypted at the application layer
-- (see backend/src/utils/totp.ts); never store a raw authenticator secret.
ALTER TABLE "staff_users" ADD COLUMN "totp_enabled" BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE "staff_users" ADD COLUMN "totp_secret" TEXT;
