import pino from 'pino';
import { config } from '@/config/env';

/**
 * Structured logging with no full-request-body/session dumps by default —
 * the legacy app logged raw print_r($data) / session contents in many
 * handlers (docs/SECURITY_AUDIT.md #13). Callers must explicitly pick the
 * fields they want logged.
 */
export const logger = pino({
  level: config.nodeEnv === 'production' ? 'info' : 'debug',
  transport: config.nodeEnv === 'production' ? undefined : { target: 'pino-pretty', options: { colorize: true } },
});
