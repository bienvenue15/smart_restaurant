import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requireSuperAdmin } from '@/middleware/superAdmin';
import { validate } from '@/middleware/validate';
import {
  createRestaurantByAdminSchema,
  extendSubscriptionSchema,
  toggleRestaurantStatusSchema,
  updateSubscriptionPlanSchema,
} from '@/validators/admin.validators';
import * as adminService from './admin.service';

export const adminRouter = Router();
adminRouter.use(requireStaffAuth, requireSuperAdmin);

adminRouter.get('/restaurants', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.listRestaurants() });
  } catch (err) {
    next(err);
  }
});

adminRouter.get('/restaurants/:id', async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.getRestaurant(req.params.id!) });
  } catch (err) {
    next(err);
  }
});

adminRouter.post('/restaurants', validate(createRestaurantByAdminSchema), async (req, res, next) => {
  try {
    res.status(201).json({ status: 'OK', data: await adminService.createRestaurant(req.body) });
  } catch (err) {
    next(err);
  }
});

adminRouter.patch('/restaurants/:id/status', validate(toggleRestaurantStatusSchema), async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.toggleRestaurantStatus(req.params.id!, req.body.isActive) });
  } catch (err) {
    next(err);
  }
});

adminRouter.post('/restaurants/:id/extend-subscription', validate(extendSubscriptionSchema), async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.extendSubscription(req.params.id!, req.body.additionalDays) });
  } catch (err) {
    next(err);
  }
});

adminRouter.get('/users', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.listPlatformUsers() });
  } catch (err) {
    next(err);
  }
});

adminRouter.get('/stats', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.getPlatformStats() });
  } catch (err) {
    next(err);
  }
});

adminRouter.get('/plans', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.listSubscriptionPlans() });
  } catch (err) {
    next(err);
  }
});

adminRouter.patch('/plans/:planName', validate(updateSubscriptionPlanSchema), async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.updateSubscriptionPlan(req.params.planName as never, req.body) });
  } catch (err) {
    next(err);
  }
});
