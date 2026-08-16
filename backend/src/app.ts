import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import cookieParser from 'cookie-parser';
import pinoHttp from 'pino-http';
import { config } from '@/config/env';
import { logger } from '@/utils/logger';
import { errorHandler } from '@/middleware/errorHandler';
import { authRouter } from '@/modules/auth/auth.routes';
import { publicMenuRouter, staffMenuRouter } from '@/modules/menu/menu.routes';
import { customerOrderRouter, staffOrderRouter } from '@/modules/orders/order.routes';

export function createApp() {
  const app = express();

  // Helmet + a same-origin-by-default CORS allow-list — the legacy app set
  // `Access-Control-Allow-Origin: *` globally, which combined with
  // cookie-based auth is a real CSRF-adjacent risk (docs/SECURITY_AUDIT.md #5).
  app.use(helmet());
  app.use(
    cors({
      origin: config.corsOrigin.length > 0 ? config.corsOrigin : false,
      credentials: true,
    }),
  );
  app.use(express.json({ limit: '2mb' }));
  app.use(cookieParser());
  app.use(pinoHttp({ logger }));

  app.get('/health', (_req, res) => res.json({ status: 'OK' }));

  app.use('/api/v1/auth', authRouter);
  app.use('/api/v1/customer/menu', publicMenuRouter);
  app.use('/api/v1/customer/orders', customerOrderRouter);
  app.use('/api/v1/staff/menu', staffMenuRouter);
  app.use('/api/v1/staff/orders', staffOrderRouter);

  app.use((_req, res) => {
    res.status(404).json({ status: 'FAIL', code: 'NOT_FOUND', message: 'Endpoint not found' });
  });

  app.use(errorHandler);

  return app;
}
