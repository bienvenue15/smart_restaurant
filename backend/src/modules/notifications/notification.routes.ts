import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import * as notificationService from './notification.service';

export const notificationRouter = Router();
notificationRouter.use(requireStaffAuth);

notificationRouter.get('/', async (req, res, next) => {
  try {
    const includeRead = req.query.includeRead === 'true';
    const notifications = await notificationService.listForStaff(req.staff!.id, includeRead);
    res.json({ status: 'OK', data: notifications });
  } catch (err) {
    next(err);
  }
});

notificationRouter.post('/:id/read', async (req, res, next) => {
  try {
    const notification = await notificationService.markRead(req.staff!.id, req.params.id!);
    res.json({ status: 'OK', data: notification });
  } catch (err) {
    next(err);
  }
});

notificationRouter.post('/read-all', async (req, res, next) => {
  try {
    await notificationService.markAllRead(req.staff!.id);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});
