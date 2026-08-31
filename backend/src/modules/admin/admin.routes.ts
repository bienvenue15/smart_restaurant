import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requireSuperAdmin } from '@/middleware/superAdmin';
import { validate } from '@/middleware/validate';
import {
  createRestaurantByAdminSchema,
  extendSubscriptionSchema,
  hardDeleteRestaurantSchema,
  assignRestaurantPlanSchema,
  togglePlatformUserStatusSchema,
  toggleRestaurantStatusSchema,
  updateSubscriptionPlanSchema,
  updateSystemSettingSchema,
} from '@/validators/admin.validators';
import * as adminService from './admin.service';
import * as backupService from './backup.service';
import { getAllSettings, setSetting, invalidateMaintenanceCache, MAINTENANCE_MODE_KEY } from '@/services/systemSettings.service';

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

adminRouter.post('/restaurants/:id/hard-delete', validate(hardDeleteRestaurantSchema), async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.hardDeleteRestaurant(req.params.id!, req.body.confirmSlug) });
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

adminRouter.patch('/restaurants/:id/plan', validate(assignRestaurantPlanSchema), async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.assignRestaurantPlan(req.params.id!, req.body.subscriptionPlan) });
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

adminRouter.patch('/users/:id/status', validate(togglePlatformUserStatusSchema), async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.togglePlatformUserStatus(req.params.id!, req.body.isActive) });
  } catch (err) {
    next(err);
  }
});

adminRouter.post('/users/:id/reset-password', async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.resetPlatformUserPassword(req.params.id!) });
  } catch (err) {
    next(err);
  }
});

adminRouter.get('/audit-log', async (req, res, next) => {
  try {
    const log = await adminService.getPlatformAuditLog({
      restaurantId: req.query.restaurantId as string | undefined,
      search: req.query.search as string | undefined,
    });
    res.json({ status: 'OK', data: log });
  } catch (err) {
    next(err);
  }
});

adminRouter.get('/settings', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await getAllSettings() });
  } catch (err) {
    next(err);
  }
});

adminRouter.patch('/settings/:key', validate(updateSystemSettingSchema), async (req, res, next) => {
  try {
    const setting = await setSetting(req.params.key!, req.body.value, req.body.description);
    // Otherwise a toggle wouldn't take effect until the middleware's own
    // cache TTL expires — see maintenanceModeGate/isMaintenanceModeEnabled.
    if (req.params.key === MAINTENANCE_MODE_KEY) invalidateMaintenanceCache();
    res.json({ status: 'OK', data: setting });
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

adminRouter.get('/analytics', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await adminService.getPlatformAnalytics() });
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

adminRouter.get('/backups', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await backupService.listBackups() });
  } catch (err) {
    next(err);
  }
});

adminRouter.post('/backups', async (req, res, next) => {
  try {
    res.status(201).json({ status: 'OK', data: await backupService.triggerBackup(req.staff!.id) });
  } catch (err) {
    next(err);
  }
});

adminRouter.get('/backups/:filename', async (req, res, next) => {
  try {
    const filePath = await backupService.resolveBackupFile(req.params.filename!);
    res.download(filePath, req.params.filename!);
  } catch (err) {
    next(err);
  }
});
