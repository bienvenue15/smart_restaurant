import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requireAnyPermission, requirePermission, staffHasPermission } from '@/middleware/permission';
import { requireActiveShift } from '@/middleware/shift';
import { validate } from '@/middleware/validate';
import { requestDiscountSchema, requestRefundSchema } from '@/validators/adjustment.validators';
import * as adjustmentService from './adjustment.service';
import * as cashService from '@/modules/cash/cash.service';

export const adjustmentRouter = Router();
adjustmentRouter.use(requireStaffAuth, requireActiveShift);

adjustmentRouter.post(
  '/orders/:orderId/discount',
  requirePermission('update_orders'),
  validate(requestDiscountSchema),
  async (req, res, next) => {
    try {
      const canAutoApprove = await staffHasPermission(req.staff!.role, 'approve_actions');
      const adjustment = await adjustmentService.requestDiscount(
        req.staff!.restaurantId!,
        req.params.orderId!,
        req.staff!.id,
        canAutoApprove,
        req.body.discountPercent,
        req.body.reason,
      );
      res.status(201).json({ status: 'OK', data: adjustment });
    } catch (err) {
      next(err);
    }
  },
);

adjustmentRouter.post(
  '/orders/:orderId/refund',
  requirePermission('refund_orders'),
  validate(requestRefundSchema),
  async (req, res, next) => {
    try {
      const canApproveRefunds = await staffHasPermission(req.staff!.role, 'process_refund');
      const adjustment = await adjustmentService.requestRefund(
        req.staff!.restaurantId!,
        req.params.orderId!,
        req.staff!.id,
        Boolean(canApproveRefunds),
        req.body.amount,
        req.body.reason,
      );
      res.status(201).json({ status: 'OK', data: adjustment });
    } catch (err) {
      next(err);
    }
  },
);

adjustmentRouter.get('/pending', requireAnyPermission(['approve_actions', 'manage_staff']), async (req, res, next) => {
  try {
    const restaurantId = req.staff!.restaurantId!;
    const [adjustments, cashCloses] = await Promise.all([
      adjustmentService.listPendingApprovals(restaurantId),
      cashService.listPendingCloses(restaurantId),
    ]);
    const pending = [...adjustments, ...cashCloses].sort(
      (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime(),
    );
    res.json({ status: 'OK', data: pending });
  } catch (err) {
    next(err);
  }
});

adjustmentRouter.post('/:id/approve', requireAnyPermission(['approve_actions', 'manage_staff']), async (req, res, next) => {
  try {
    await adjustmentService.approveAdjustment(req.staff!.restaurantId!, req.params.id!, req.staff!.id);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});

adjustmentRouter.post('/:id/reject', requireAnyPermission(['approve_actions', 'manage_staff']), async (req, res, next) => {
  try {
    await adjustmentService.rejectAdjustment(req.staff!.restaurantId!, req.params.id!, req.staff!.id);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});
