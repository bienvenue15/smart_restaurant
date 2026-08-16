import { Router } from 'express';
import { requireCustomerSession, requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { validate } from '@/middleware/validate';
import { assignWaiterCallSchema, createWaiterCallSchema } from '@/validators/waiterCall.validators';
import * as waiterCallService from './waiterCall.service';

export const customerWaiterCallRouter = Router();

customerWaiterCallRouter.post('/', requireCustomerSession, validate(createWaiterCallSchema), async (req, res, next) => {
  try {
    const { restaurantId, tableId } = req.customerSession!;
    const call = await waiterCallService.createCall(restaurantId, tableId, req.body.requestType, req.body.message, req.body.priority);
    res.status(201).json({ status: 'OK', data: call });
  } catch (err) {
    next(err);
  }
});

export const staffWaiterCallRouter = Router();
staffWaiterCallRouter.use(requireStaffAuth);

staffWaiterCallRouter.get('/', requirePermission('view_tables'), async (req, res, next) => {
  try {
    const calls = await waiterCallService.listCalls(req.staff!.restaurantId!, req.query.status as never);
    res.json({ status: 'OK', data: calls });
  } catch (err) {
    next(err);
  }
});

// Deliberately just requireStaffAuth (any logged-in staff) to accept a
// call — matches the legacy's actual behavior for this one action
// (docs/SECURITY_AUDIT.md #8 flags the INVERSE gap: legacy had no
// Permission:: check at all here, only a session check. We keep the same
// "any staff can claim a call" business rule but do so as an explicit,
// reviewed decision rather than a silent omission.)
staffWaiterCallRouter.post('/:id/accept', async (req, res, next) => {
  try {
    const call = await waiterCallService.acceptCall(req.staff!.restaurantId!, req.params.id!, req.staff!.id);
    res.json({ status: 'OK', data: call });
  } catch (err) {
    next(err);
  }
});

staffWaiterCallRouter.post('/:id/assign', requirePermission('manage_tables'), validate(assignWaiterCallSchema), async (req, res, next) => {
  try {
    const call = await waiterCallService.assignCall(req.staff!.restaurantId!, req.params.id!, req.body.staffId);
    res.json({ status: 'OK', data: call });
  } catch (err) {
    next(err);
  }
});

staffWaiterCallRouter.post('/:id/complete', requirePermission('manage_tables'), async (req, res, next) => {
  try {
    const call = await waiterCallService.completeCall(req.staff!.restaurantId!, req.params.id!);
    res.json({ status: 'OK', data: call });
  } catch (err) {
    next(err);
  }
});
