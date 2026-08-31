import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { validate } from '@/middleware/validate';
import { liabilityReasonSchema } from '@/validators/liability.validators';
import * as liabilityService from './liability.service';

export const liabilityRouter = Router();
liabilityRouter.use(requireStaffAuth);

// Any staff member may view liabilities — waiters/kitchen/cashier see only
// their own (service-layer scoping), admin/manager see the whole
// restaurant. Mirrors the legacy's `staff_get_my_liabilities` (no
// permission code, session-only) vs `staff_get_all_liabilities`
// (admin/manager-only) split, docs/CURRENT_SYSTEM_AUDIT.md §"Api endpoint
// inventory" — collapsed into one endpoint with service-layer scoping
// instead of two, since the access rule is "your own data" either way.
liabilityRouter.get('/', async (req, res, next) => {
  try {
    const liabilities = await liabilityService.listLiabilities(
      req.staff!.restaurantId!,
      req.staff!.id,
      req.staff!.role,
      req.query.status as string | undefined,
    );
    res.json({ status: 'OK', data: liabilities });
  } catch (err) {
    next(err);
  }
});

liabilityRouter.get('/stats', requirePermission('view_reports'), async (req, res, next) => {
  try {
    const stats = await liabilityService.getStats(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: stats });
  } catch (err) {
    next(err);
  }
});

// Every staff member's own accountability summary (their ACTIVE exposure,
// WAIVED total, etc.) — no `view_reports` gate, since it's scoped to the
// caller's own data only, unlike /stats which is restaurant-wide.
liabilityRouter.get('/my-summary', async (req, res, next) => {
  try {
    const summary = await liabilityService.getMySummary(req.staff!.restaurantId!, req.staff!.id);
    res.json({ status: 'OK', data: summary });
  } catch (err) {
    next(err);
  }
});

liabilityRouter.get('/waived-by-staff', requirePermission('view_reports'), async (req, res, next) => {
  try {
    const breakdown = await liabilityService.getWaivedByStaff(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: breakdown });
  } catch (err) {
    next(err);
  }
});

liabilityRouter.post('/:id/waive', requirePermission('manage_staff'), validate(liabilityReasonSchema), async (req, res, next) => {
  try {
    await liabilityService.waiveLiability(req.params.id!, req.staff!.id, req.body.reason);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});

liabilityRouter.post('/:id/mark-loss', requirePermission('manage_staff'), validate(liabilityReasonSchema), async (req, res, next) => {
  try {
    await liabilityService.markAsLoss(req.params.id!, req.staff!.id, req.body.reason);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});
