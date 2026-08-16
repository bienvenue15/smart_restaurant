import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { validate } from '@/middleware/validate';
import { dateRangeSchema } from '@/validators/report.validators';
import * as reportService from './report.service';

export const reportRouter = Router();
reportRouter.use(requireStaffAuth);

reportRouter.get('/dashboard', async (req, res, next) => {
  try {
    const stats = await reportService.getDashboardStats(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: stats });
  } catch (err) {
    next(err);
  }
});

reportRouter.get('/sales', requirePermission('view_reports'), validate(dateRangeSchema, 'query'), async (req, res, next) => {
  try {
    const { startDate, endDate } = req.query as unknown as { startDate: Date; endDate: Date };
    const report = await reportService.getSalesReport(req.staff!.restaurantId!, startDate, endDate);
    res.json({ status: 'OK', data: report });
  } catch (err) {
    next(err);
  }
});

reportRouter.get('/top-items', requirePermission('view_reports'), validate(dateRangeSchema, 'query'), async (req, res, next) => {
  try {
    const { startDate, endDate, limit } = req.query as unknown as { startDate: Date; endDate: Date; limit: number };
    const items = await reportService.getTopMenuItems(req.staff!.restaurantId!, startDate, endDate, limit);
    res.json({ status: 'OK', data: items });
  } catch (err) {
    next(err);
  }
});
