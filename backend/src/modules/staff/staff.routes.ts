import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { validate } from '@/middleware/validate';
import { createStaffSchema, updateStaffSchema } from '@/validators/staff.validators';
import * as staffService from './staff.service';

export const staffRouter = Router();
staffRouter.use(requireStaffAuth);

staffRouter.get('/', requirePermission('manage_staff'), async (req, res, next) => {
  try {
    const staff = await staffService.listStaff(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: staff });
  } catch (err) {
    next(err);
  }
});

staffRouter.post('/', requirePermission('manage_staff'), validate(createStaffSchema), async (req, res, next) => {
  try {
    const staff = await staffService.createStaff(req.staff!.restaurantId!, req.body);
    res.status(201).json({ status: 'OK', data: staff });
  } catch (err) {
    next(err);
  }
});

staffRouter.patch('/:id', requirePermission('manage_staff'), validate(updateStaffSchema), async (req, res, next) => {
  try {
    const staff = await staffService.updateStaff(req.staff!.restaurantId!, req.params.id!, req.body);
    res.json({ status: 'OK', data: staff });
  } catch (err) {
    next(err);
  }
});

staffRouter.delete('/:id', requirePermission('manage_staff'), async (req, res, next) => {
  try {
    await staffService.deleteStaff(req.staff!.restaurantId!, req.params.id!);
    res.status(204).send();
  } catch (err) {
    next(err);
  }
});

// Shift endpoints are intentionally NOT behind requirePermission/requireActiveShift —
// clocking in is how a staff member BECOMES active-shift in the first place
// (docs/CURRENT_SYSTEM_AUDIT.md §3 lists these as the legacy shift-gating whitelist).
staffRouter.post('/me/clock-in', async (req, res, next) => {
  try {
    const shift = await staffService.clockIn(req.staff!.id, req.staff!.restaurantId!);
    res.status(201).json({ status: 'OK', data: shift });
  } catch (err) {
    next(err);
  }
});

staffRouter.post('/me/clock-out', async (req, res, next) => {
  try {
    const shift = await staffService.clockOut(req.staff!.id);
    res.json({ status: 'OK', data: shift });
  } catch (err) {
    next(err);
  }
});

staffRouter.get('/me/shift-status', async (req, res, next) => {
  try {
    const status = await staffService.getShiftStatus(req.staff!.id);
    res.json({ status: 'OK', data: status });
  } catch (err) {
    next(err);
  }
});
