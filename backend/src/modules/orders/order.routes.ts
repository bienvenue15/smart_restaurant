import { Router } from 'express';
import { requireCustomerSession, requireStaffAuth } from '@/middleware/auth';
import { requireAnyPermission, requirePermission, staffHasPermission } from '@/middleware/permission';
import { requireActiveShift } from '@/middleware/shift';
import { validate } from '@/middleware/validate';
import { BadRequest, Forbidden } from '@/utils/httpError';
import {
  addOrderItemsSchema,
  assignOrderSchema,
  createOrderSchema,
  guestHeardAboutSchema,
  recordPaymentSchema,
  updateOrderItemStatusSchema,
  updateOrderStatusSchema,
} from '@/validators/order.validators';
import * as orderService from './order.service';
import { extendLockOnActivity, endSession } from '@/modules/customerSession/customerSession.service';
import { assertFeature } from '@/modules/subscriptions/subscription.service';
import { config } from '@/config/env';

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

customerOrderRouter.get('/', requireCustomerSession, async (req, res, next) => {
  try {
    const { restaurantId, tableId } = req.customerSession!;
    const orders = await orderService.listOrdersForTableSession(restaurantId, tableId);
    res.json({ status: 'OK', data: orders });
  } catch (err) {
    next(err);
  }
});

customerOrderRouter.get('/:orderId', requireCustomerSession, async (req, res, next) => {
  try {
    const { restaurantId, tableId } = req.customerSession!;
    const order = await orderService.getOrderForTableSession(restaurantId, tableId, req.params.orderId!);
    res.json({ status: 'OK', data: order });
  } catch (err) {
    next(err);
  }
});

customerOrderRouter.post('/:orderId/items', requireCustomerSession, validate(addOrderItemsSchema), async (req, res, next) => {
  try {
    const { restaurantId, tableId, deviceTableLockId } = req.customerSession!;
    const order = await orderService.addItemsToOrder(restaurantId, tableId, req.params.orderId!, req.body.items);
    await extendLockOnActivity(deviceTableLockId);
    res.json({ status: 'OK', data: order });
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

customerOrderRouter.post('/:orderId/heard-about', requireCustomerSession, validate(guestHeardAboutSchema), async (req, res, next) => {
  try {
    const { restaurantId, tableId, deviceTableLockId } = req.customerSession!;
    const row = await orderService.recordGuestHeardAbout(restaurantId, req.params.orderId!, req.body, tableId);
    await endSession(deviceTableLockId);
    res.clearCookie('sr_customer_session', {
      httpOnly: true,
      secure: config.nodeEnv === 'production',
      sameSite: 'lax',
      path: '/',
    });
    res.status(201).json({ status: 'OK', data: row });
  } catch (err) {
    next(err);
  }
});

export const staffOrderRouter = Router();
staffOrderRouter.use(requireStaffAuth);

staffOrderRouter.get('/', requirePermission('view_orders'), async (req, res, next) => {
  try {
    const prepQueueOnly = req.query.view === 'kitchen';
    if (prepQueueOnly && !(await staffHasPermission(req.staff!.role, 'view_kitchen'))) {
      throw Forbidden('Missing permission: view_kitchen');
    }
    // Kitchen-role staff always get the prep queue — it is their job, not
    // a premium add-on. Other roles (manager watching the KDS) still need
    // the plan feature.
    if (prepQueueOnly && req.staff!.role !== 'KITCHEN') {
      await assertFeature(req.staff!.restaurantId!, 'kitchen_display');
    }
    const orders = await orderService.listOrders(
      req.staff!.restaurantId!,
      req.staff!.id,
      req.staff!.role,
      req.query.status as never,
      prepQueueOnly,
    );
    res.json({ status: 'OK', data: orders });
  } catch (err) {
    next(err);
  }
});

// Registered before the `/:orderId` param route below — otherwise Express
// would match this literal path as an orderId lookup first.
staffOrderRouter.get('/onshift-waiters', requireAnyPermission(['approve_actions', 'manage_staff']), async (req, res, next) => {
  try {
    const waiters = await orderService.listOnShiftWaiters(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: waiters });
  } catch (err) {
    next(err);
  }
});

staffOrderRouter.get('/:orderId', requirePermission('view_orders'), async (req, res, next) => {
  try {
    const order = await orderService.getOrderById(req.staff!.restaurantId!, req.params.orderId!, req.staff!.id, req.staff!.role);
    res.json({ status: 'OK', data: order });
  } catch (err) {
    next(err);
  }
});

// Client-triggered on a polling interval from the staff frontend (no
// server-side scheduler exists), same model as the legacy checkDelayedOrders
// endpoint it replaces.
staffOrderRouter.post('/check-delays', requirePermission('view_orders'), async (req, res, next) => {
  try {
    const result = await orderService.checkDelayedOrders(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: result });
  } catch (err) {
    next(err);
  }
});

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

staffOrderRouter.post(
  '/:orderId/assign',
  requireActiveShift,
  requireAnyPermission(['approve_actions', 'manage_staff']),
  validate(assignOrderSchema),
  async (req, res, next) => {
    try {
      const order = await orderService.assignOrderToWaiter(req.staff!.restaurantId!, req.params.orderId!, req.body.staffId);
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
        req.staff!.role,
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
