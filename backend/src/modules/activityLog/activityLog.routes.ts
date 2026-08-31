import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import * as activityLogService from './activityLog.service';

export const activityLogRouter = Router();
activityLogRouter.use(requireStaffAuth);

activityLogRouter.get('/', requirePermission('view_activity_log'), async (req, res, next) => {
  try {
    const search = typeof req.query.search === 'string' ? req.query.search : undefined;
    const log = await activityLogService.getActivityLog(req.staff!.restaurantId!, { search });
    res.json({ status: 'OK', data: log });
  } catch (err) {
    next(err);
  }
});
