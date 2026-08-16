import { Router } from 'express';
import { requireCustomerSession, requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { requireActiveShift } from '@/middleware/shift';
import { validate } from '@/middleware/validate';
import { BadRequest } from '@/utils/httpError';
import {
  createOrderSchema,
  recordPaymentSchema,
  updateOrderItemStatusSchema,
  updateOrderStatusSchema,
} from '@/validators/order.validators';
import * as orderService from './order.service';
import { extendLockOnActivity } from '@/modules/customerSession/customerSession.service';

export const customerOrderRouter = Router();

customerOrderRouter.post('/', requireCustomerSession, validate(createOrderSchema), async (req, res, next) => {
  try {
    const { restaurantId, tableId, deviceTableLockId } = req.customerSession!;
    if (req.body.tableId !== tableId) throw BadRequest('Table mismatch for this session');
    const order = await orderService.createOrder(restaurantId, tableId, req.body.items, req.body.specialInstructions);
    await extendLockOnActivity(deviceTableLockId);
    res.status(201).json({ status: 'OK', data: order });
  } catch (err) {
    next(err);
  }
});

customerOrderRouter.post('/:orderId/cancel', requireCustomerSession, async (req, res, next) => {
  try {
    const order = await orderService.cancelOrder(req.customerSession!.restaurantId, req.params.orderId!);
    res.json({ status: 'OK', data: order });
  } catch (err) {
    next(err);
  }
});

export const staffOrderRouter = Router();
staffOrderRouter.use(requireStaffAuth);

staffOrderRouter.patch(
  '/:orderId/status',
  requireActiveShift,
  requirePermission('manage_orders'),
  validate(updateOrderStatusSchema),
  async (req, res, next) => {
    try {
      const order = await orderService.updateOrderStatus(req.staff!.restaurantId!, req.params.orderId!, req.body.status, req.staff!.id);
      res.json({ status: 'OK', data: order });
    } catch (err) {
      next(err);
    }
  },
);

staffOrderRouter.patch(
  '/items/:orderItemId/status',
  requireActiveShift,
  requirePermission('modify_order'),
  validate(updateOrderItemStatusSchema),
  async (req, res, next) => {
    try {
      const order = await orderService.updateOrderItemStatus(
        req.staff!.restaurantId!,
        req.params.orderItemId!,
        req.body.status,
        req.staff!.id,
      );
      res.json({ status: 'OK', data: order });
    } catch (err) {
      next(err);
    }
  },
);

staffOrderRouter.post(
  '/:orderId/payments',
  requireActiveShift,
  requirePermission('accept_payment'),
  validate(recordPaymentSchema),
  async (req, res, next) => {
    try {
      const { amount, receivedAmount, paymentMethod, paymentReference } = req.body;
      const order = await orderService.recordPayment(
        req.staff!.restaurantId!,
        req.params.orderId!,
        amount,
        receivedAmount,
        paymentMethod,
        req.staff!.id,
        paymentReference,
      );
      res.json({ status: 'OK', data: order });
    } catch (err) {
      next(err);
    }
  },
);
