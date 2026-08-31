import { Router } from 'express';
import { requireCustomerSession, requireStaffAuth } from '@/middleware/auth';
import { requireAnyPermission, requirePermission } from '@/middleware/permission';
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

staffWaiterCallRouter.get('/', requirePermission('handle_waiter_calls'), async (req, res, next) => {
  try {
    const calls = await waiterCallService.listCalls(req.staff!.restaurantId!, req.query.status as never);
    res.json({ status: 'OK', data: calls });
  } catch (err) {
    next(err);
  }
});

staffWaiterCallRouter.post('/:id/accept', requirePermission('handle_waiter_calls'), async (req, res, next) => {
  try {
    const call = await waiterCallService.acceptCall(req.staff!.restaurantId!, req.params.id!, req.staff!.id);
    res.json({ status: 'OK', data: call });
  } catch (err) {
    next(err);
  }
});

staffWaiterCallRouter.post('/:id/assign', requireAnyPermission(['approve_actions', 'manage_staff']), validate(assignWaiterCallSchema), async (req, res, next) => {
  try {
    const call = await waiterCallService.assignCall(req.staff!.restaurantId!, req.params.id!, req.body.staffId);
    res.json({ status: 'OK', data: call });
  } catch (err) {
    next(err);
  }
});

staffWaiterCallRouter.post('/:id/complete', requirePermission('handle_waiter_calls'), async (req, res, next) => {
  try {
    const call = await waiterCallService.completeCall(req.staff!.restaurantId!, req.params.id!);
    res.json({ status: 'OK', data: call });
  } catch (err) {
    next(err);
  }
});
