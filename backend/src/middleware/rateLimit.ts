import rateLimit from 'express-rate-limit';

const jsonMessage = (message: string) => ({
  status: 'FAIL' as const,
  code: 'RATE_LIMITED',
  message,
});

/**
 * In-memory counters (fine for the single-process modular monolith).
 * A shared store is only needed if we run multiple API instances —
 * see docs/SECURITY_AUDIT.md #10.
 */
export const loginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 20,
  skipSuccessfulRequests: true,
  standardHeaders: true,
  legacyHeaders: false,
  message: jsonMessage('Too many login attempts. Try again in a few minutes.'),
});

export const passwordResetLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 5,
  standardHeaders: true,
  legacyHeaders: false,
  message: jsonMessage('Too many password-reset attempts. Try again in a few minutes.'),
});

export const publicFormLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 10,
  standardHeaders: true,
  legacyHeaders: false,
  message: jsonMessage('Too many submissions. Try again in a few minutes.'),
});
