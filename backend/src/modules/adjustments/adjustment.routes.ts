import { Router } from 'express';
import { prisma } from '@/config/prisma';
import { requireStaffAuth } from '@/middleware/auth';
import { requireAnyPermission, requirePermission } from '@/middleware/permission';
import { requireActiveShift } from '@/middleware/shift';
import { validate } from '@/middleware/validate';
import { requestDiscountSchema, requestRefundSchema } from '@/validators/adjustment.validators';
import * as adjustmentService from './adjustment.service';

export const adjustmentRouter = Router();
adjustmentRouter.use(requireStaffAuth, requireActiveShift);

adjustmentRouter.post(
  '/orders/:orderId/discount',
  requirePermission('update_orders'),
  validate(requestDiscountSchema),
  async (req, res, next) => {
    try {
      const staff = await prisma.staffUser.findUniqueOrThrow({ where: { id: req.staff!.id } });
      const adjustment = await adjustmentService.requestDiscount(
        req.staff!.restaurantId!,
        req.params.orderId!,
        req.staff!.id,
        Number(staff.maxDiscountPercent),
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
      const canApproveRefunds = await prisma.rolePermission.findUnique({
        where: { role_permissionCode: { role: req.staff!.role, permissionCode: 'process_refund' } },
      });
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
    const pending = await adjustmentService.listPendingApprovals(req.staff!.restaurantId!);
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
