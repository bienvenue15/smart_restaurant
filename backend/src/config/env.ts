import 'dotenv/config';

interface MailConfig {
  disableDelivery: boolean;
  fromAddress: string;
  fromName: string;
  smtpHost: string;
  smtpPort: number;
  smtpUsername: string;
  smtpPassword: string;
  smtpEncryption: string;
  notifyAddress: string;
}

interface SmsConfig {
  disableDelivery: boolean;
  username: string;
  apiKey: string;
  senderId: string;
  apiUrl: string;
}

interface WhatsAppConfig {
  disableDelivery: boolean;
  token: string;
  phoneNumberId: string;
}

interface AppConfig {
  nodeEnv: string;
  port: number;
  databaseUrl: string;
  jwtAccessSecret: string;
  jwtRefreshSecret: string;
  jwtAccessTtl: string;
  jwtRefreshTtl: string;
  corsOrigin: string[];
  frontendUrl: string;
  mail: MailConfig;
  sms: SmsConfig;
  whatsapp: WhatsAppConfig;
}

/**
 * Fails fast on boot if required secrets are missing — the legacy app's
 * src/config.php instead fell back to hardcoded production credentials
 * when env vars weren't set (see docs/SECURITY_AUDIT.md #1). We do not
 * repeat that pattern: no fallback values for secrets, ever.
 */
function required(name: string): string {
  const value = process.env[name];
  if (!value || value.trim() === '') {
    throw new Error(`Missing required environment variable: ${name}`);
  }
  return value;
}

function loadConfig(): AppConfig {
  const nodeEnv = process.env.NODE_ENV ?? 'development';
  const jwtAccessSecret = required('JWT_ACCESS_SECRET');
  const jwtRefreshSecret = required('JWT_REFRESH_SECRET');
  const corsOrigin = (process.env.CORS_ORIGIN ?? '').split(',').map((o) => o.trim()).filter(Boolean);

  if (jwtAccessSecret.length < 32 || jwtRefreshSecret.length < 32) {
    throw new Error('JWT_ACCESS_SECRET and JWT_REFRESH_SECRET must each be at least 32 characters');
  }

  if (nodeEnv === 'production') {
    if (corsOrigin.length === 0) {
      throw new Error('CORS_ORIGIN is required in production (public site origin, never *)');
    }
    if (corsOrigin.includes('*')) {
      throw new Error('CORS_ORIGIN cannot be * when cookies are used');
    }
  }

  return {
    nodeEnv,
    port: Number(process.env.PORT ?? 4000),
    databaseUrl: required('DATABASE_URL'),
    jwtAccessSecret,
    jwtRefreshSecret,
    jwtAccessTtl: process.env.JWT_ACCESS_TTL ?? '15m',
    jwtRefreshTtl: process.env.JWT_REFRESH_TTL ?? '7d',
    corsOrigin,
    frontendUrl:
      process.env.FRONTEND_URL?.trim() ||
      (process.env.CORS_ORIGIN ?? 'http://localhost:3000').split(',')[0]!.trim() ||
      'http://localhost:3000',
    mail: {
      // Unset or anything other than "false" means do not send — local/dev
      // default is a no-op so we never accidentally hit a real inbox.
      disableDelivery: process.env.MAIL_DISABLE_DELIVERY !== 'false',
      fromAddress: process.env.MAIL_FROM_ADDRESS ?? '',
      fromName: process.env.MAIL_FROM_NAME ?? 'Smart Restaurant',
      smtpHost: process.env.MAIL_SMTP_HOST ?? '',
      smtpPort: Number(process.env.MAIL_SMTP_PORT ?? 587),
      smtpUsername: process.env.MAIL_SMTP_USERNAME ?? '',
      smtpPassword: process.env.MAIL_SMTP_PASSWORD ?? '',
      smtpEncryption: process.env.MAIL_SMTP_ENCRYPTION ?? 'tls',
      notifyAddress: process.env.MAIL_NOTIFY_ADDRESS ?? '',
    },
    sms: {
      disableDelivery: process.env.SMS_DISABLE_DELIVERY !== 'false',
      username: process.env.SMS_AT_USERNAME ?? '',
      apiKey: process.env.SMS_AT_API_KEY ?? '',
      senderId: process.env.SMS_SENDER_ID ?? '',
      apiUrl: process.env.SMS_AT_API_URL ?? 'https://api.africastalking.com/version1/messaging',
    },
    whatsapp: {
      disableDelivery: process.env.WHATSAPP_DISABLE_DELIVERY !== 'false',
      token: process.env.WHATSAPP_TOKEN ?? '',
      phoneNumberId: process.env.WHATSAPP_PHONE_NUMBER_ID ?? '',
    },
  };
}

export const config = loadConfig();
