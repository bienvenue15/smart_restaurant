import 'dotenv/config';

interface AppConfig {
  nodeEnv: string;
  port: number;
  databaseUrl: string;
  jwtAccessSecret: string;
  jwtRefreshSecret: string;
  jwtAccessTtl: string;
  jwtRefreshTtl: string;
  corsOrigin: string[];
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
  return {
    nodeEnv: process.env.NODE_ENV ?? 'development',
    port: Number(process.env.PORT ?? 4000),
    databaseUrl: required('DATABASE_URL'),
    jwtAccessSecret: required('JWT_ACCESS_SECRET'),
    jwtRefreshSecret: required('JWT_REFRESH_SECRET'),
    jwtAccessTtl: process.env.JWT_ACCESS_TTL ?? '15m',
    jwtRefreshTtl: process.env.JWT_REFRESH_TTL ?? '7d',
    corsOrigin: (process.env.CORS_ORIGIN ?? '').split(',').map((o) => o.trim()).filter(Boolean),
  };
}

export const config = loadConfig();
