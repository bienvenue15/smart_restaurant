import { Router } from 'express';
import { requireCustomerSession, requireStaffAuth } from '@/middleware/auth';
import { requireSuperAdmin } from '@/middleware/superAdmin';
import { validate } from '@/middleware/validate';
import { createAnnouncementSchema, updateAnnouncementSchema } from '@/validators/announcement.validators';
import * as announcementService from './announcement.service';

// Superadmin-managed platform broadcasts.
export const adminAnnouncementRouter = Router();
adminAnnouncementRouter.use(requireStaffAuth, requireSuperAdmin);

adminAnnouncementRouter.get('/', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await announcementService.listAllAnnouncements() });
  } catch (err) {
    next(err);
  }
});

adminAnnouncementRouter.get('/stats', async (_req, res, next) => {
  try {
    res.json({ status: 'OK', data: await announcementService.getAnnouncementStats() });
  } catch (err) {
    next(err);
  }
});

adminAnnouncementRouter.post('/', validate(createAnnouncementSchema), async (req, res, next) => {
  try {
    const announcement = await announcementService.createAnnouncement(req.body, req.staff!.id);
    res.status(201).json({ status: 'OK', data: announcement });
  } catch (err) {
    next(err);
  }
});

adminAnnouncementRouter.patch('/:id', validate(updateAnnouncementSchema), async (req, res, next) => {
  try {
    const announcement = await announcementService.updateAnnouncement(req.params.id!, req.body);
    res.json({ status: 'OK', data: announcement });
  } catch (err) {
    next(err);
  }
});

adminAnnouncementRouter.delete('/:id', async (req, res, next) => {
  try {
    await announcementService.deleteAnnouncement(req.params.id!);
    res.status(204).send();
  } catch (err) {
    next(err);
  }
});

adminAnnouncementRouter.post('/:id/toggle', async (req, res, next) => {
  try {
    const announcement = await announcementService.toggleAnnouncement(req.params.id!);
    res.json({ status: 'OK', data: announcement });
  } catch (err) {
    next(err);
  }
});

// Restaurant-staff-facing: see active announcements targeted at them, dismiss.
export const staffAnnouncementRouter = Router();
staffAnnouncementRouter.use(requireStaffAuth);

staffAnnouncementRouter.get('/', async (req, res, next) => {
  try {
    const announcements = await announcementService.listActiveForStaff(req.staff!.restaurantId!, req.staff!.id, req.staff!.role);
    res.json({ status: 'OK', data: announcements });
  } catch (err) {
    next(err);
  }
});

staffAnnouncementRouter.post('/:id/dismiss', async (req, res, next) => {
  try {
    await announcementService.dismissAnnouncement(req.params.id!, req.staff!.id);
    res.status(204).send();
  } catch (err) {
    next(err);
  }
});

export const customerAnnouncementRouter = Router();
customerAnnouncementRouter.use(requireCustomerSession);

customerAnnouncementRouter.get('/', async (req, res, next) => {
  try {
    const announcements = await announcementService.listActiveForCustomer(req.customerSession!.restaurantId);
    res.json({ status: 'OK', data: announcements });
  } catch (err) {
    next(err);
  }
});
