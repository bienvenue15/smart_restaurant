import path from 'node:path';
import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import cookieParser from 'cookie-parser';
import pinoHttp from 'pino-http';
import { config } from '@/config/env';
import { logger } from '@/utils/logger';
import { errorHandler } from '@/middleware/errorHandler';
import { authRouter } from '@/modules/auth/auth.routes';
import { customerSessionRouter } from '@/modules/customerSession/customerSession.routes';
import { publicMenuRouter, staffMenuRouter } from '@/modules/menu/menu.routes';
import { customerOrderRouter, staffOrderRouter } from '@/modules/orders/order.routes';
import { publicRestaurantRouter, staffRestaurantRouter } from '@/modules/restaurants/restaurant.routes';
import { staffRouter } from '@/modules/staff/staff.routes';
import { staffTableRouter } from '@/modules/tables/table.routes';
import { customerWaiterCallRouter, staffWaiterCallRouter } from '@/modules/waiterCalls/waiterCall.routes';
import { cashRouter } from '@/modules/cash/cash.routes';
import { reportRouter } from '@/modules/reports/report.routes';
import { adminRouter } from '@/modules/admin/admin.routes';
import { liabilityRouter } from '@/modules/liability/liability.routes';
import { notificationRouter } from '@/modules/notifications/notification.routes';
import { activityLogRouter } from '@/modules/activityLog/activityLog.routes';
import { adjustmentRouter } from '@/modules/adjustments/adjustment.routes';
import { adminSupportMessageRouter, adminSupportRouter, publicSupportRouter, staffSupportRouter } from '@/modules/support/support.routes';
import { adminAnnouncementRouter, customerAnnouncementRouter, staffAnnouncementRouter } from '@/modules/announcements/announcement.routes';
import { maintenanceModeGate } from '@/middleware/maintenanceMode';
import { loginLimiter, passwordResetLimiter, publicFormLimiter } from '@/middleware/rateLimit';
import { requireCustomerSession, requireStaffAuth } from '@/middleware/auth';
import { streamCustomerEvents, streamStaffEvents } from '@/modules/realtime/realtime.routes';

export function createApp() {
  const app = express();
  if (config.nodeEnv === 'production') {
    app.set('trust proxy', 1);
  }

  app.use(helmet());
  app.use(
    cors({
      origin(origin, callback) {
        if (!origin) {
          callback(null, true);
          return;
        }
        if (config.corsOrigin.includes(origin)) {
          callback(null, true);
          return;
        }
        // Dev servers often land on :3001 when :3000 is taken.
        if (
          config.nodeEnv !== 'production' &&
          /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/.test(origin)
        ) {
          callback(null, true);
          return;
        }
        callback(null, false);
      },
      credentials: true,
    }),
  );
  app.use(express.json({ limit: '2mb' }));
  app.use(cookieParser());
  app.use(pinoHttp({ logger }));

  app.get('/health', (_req, res) => res.json({ status: 'OK' }));
  app.use(maintenanceModeGate);

  app.use('/api/v1/auth/login', loginLimiter);
  app.use('/api/v1/auth/login/2fa', loginLimiter);
  app.use('/api/v1/auth/forgot-password', passwordResetLimiter);
  app.use('/api/v1/auth/reset-password', passwordResetLimiter);
  app.use('/api/v1/support/messages', publicFormLimiter);
  app.use('/api/v1/restaurants/register', publicFormLimiter);

  // Helmet's default Cross-Origin-Resource-Policy (same-origin) would
  // otherwise block the frontend (a different origin in dev/prod) from
  // rendering these images in <img> tags — relax it for this path only.
  app.use(
    '/uploads',
    (_req, res, next) => {
      res.setHeader('Cross-Origin-Resource-Policy', 'cross-origin');
      next();
    },
    express.static(path.resolve(__dirname, '../uploads')),
  );

  app.use('/api/v1/auth', authRouter);
  app.use('/api/v1/restaurants', publicRestaurantRouter);
  app.use('/api/v1/customer/session', customerSessionRouter);
  app.use('/api/v1/customer/menu', publicMenuRouter);
  app.use('/api/v1/customer/orders', customerOrderRouter);
  app.use('/api/v1/staff/menu', staffMenuRouter);
  app.use('/api/v1/staff/orders', staffOrderRouter);
  app.use('/api/v1/staff/tables', staffTableRouter);
  app.use('/api/v1/staff/users', staffRouter);
  app.use('/api/v1/staff/restaurants', staffRestaurantRouter);
  app.use('/api/v1/customer/waiter-calls', customerWaiterCallRouter);
  app.use('/api/v1/staff/waiter-calls', staffWaiterCallRouter);
  app.use('/api/v1/staff/cash', cashRouter);
  app.use('/api/v1/staff/reports', reportRouter);
  app.use('/api/v1/admin', adminRouter);
  app.use('/api/v1/staff/liabilities', liabilityRouter);
  app.use('/api/v1/staff/notifications', notificationRouter);
  app.use('/api/v1/staff/activity-log', activityLogRouter);
  app.use('/api/v1/staff/adjustments', adjustmentRouter);
  app.use('/api/v1/staff/support-tickets', staffSupportRouter);
  app.use('/api/v1/support', publicSupportRouter);
  app.use('/api/v1/admin/support-tickets', adminSupportRouter);
  app.use('/api/v1/admin/support-messages', adminSupportMessageRouter);
  app.use('/api/v1/staff/announcements', staffAnnouncementRouter);
  app.use('/api/v1/customer/announcements', customerAnnouncementRouter);
  app.use('/api/v1/admin/announcements', adminAnnouncementRouter);

  app.get('/api/v1/staff/events', requireStaffAuth, streamStaffEvents);
  app.get('/api/v1/customer/events', requireCustomerSession, streamCustomerEvents);

  app.use((_req, res) => {
    res.status(404).json({ status: 'FAIL', code: 'NOT_FOUND', message: 'Endpoint not found' });
  });

  app.use(errorHandler);

  return app;
}
