import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requireAnyPermission, requireCashHandlingFlag, requirePermission, staffHasPermission } from '@/middleware/permission';
import { requireActiveShift } from '@/middleware/shift';
import { validate } from '@/middleware/validate';
import { closeCashSessionSchema, openCashSessionSchema, recordCashTransactionSchema } from '@/validators/cash.validators';
import * as cashService from './cash.service';

export const cashRouter = Router();
cashRouter.use(requireStaffAuth, requireActiveShift);

const requireCashier = [requirePermission('handle_cash'), requireCashHandlingFlag()];

cashRouter.post('/sessions', ...requireCashier, validate(openCashSessionSchema), async (req, res, next) => {
  try {
    const session = await cashService.openSession(req.staff!.restaurantId!, req.staff!.id, req.body.openingBalance);
    res.status(201).json({ status: 'OK', data: session });
  } catch (err) {
    next(err);
  }
});

cashRouter.get('/sessions/current', ...requireCashier, async (req, res, next) => {
  try {
    const session = await cashService.getCurrentSession(req.staff!.id);
    res.json({ status: 'OK', data: session });
  } catch (err) {
    next(err);
  }
});

cashRouter.get('/sessions/history', ...requireCashier, async (req, res, next) => {
  try {
    const sessions = await cashService.getHistory(req.staff!.restaurantId!, req.staff!.id);
    res.json({ status: 'OK', data: sessions });
  } catch (err) {
    next(err);
  }
});

cashRouter.post('/sessions/:id/close', ...requireCashier, validate(closeCashSessionSchema), async (req, res, next) => {
  try {
    const canFinalize = await staffHasPermission(req.staff!.role, 'approve_actions');
    const session = await cashService.requestClose(
      req.staff!.restaurantId!,
      req.params.id!,
      req.staff!.id,
      req.body.closingBalance,
      canFinalize,
    );
    res.json({ status: 'OK', data: session });
  } catch (err) {
    next(err);
  }
});

cashRouter.post(
  '/sessions/:id/approve-close',
  requireAnyPermission(['approve_actions', 'manage_staff']),
  async (req, res, next) => {
    try {
      const session = await cashService.approveClose(req.staff!.restaurantId!, req.params.id!, req.staff!.id);
      res.json({ status: 'OK', data: session });
    } catch (err) {
      next(err);
    }
  },
);

cashRouter.post(
  '/sessions/:id/reject-close',
  requireAnyPermission(['approve_actions', 'manage_staff']),
  async (req, res, next) => {
    try {
      const session = await cashService.rejectClose(req.staff!.restaurantId!, req.params.id!, req.staff!.id);
      res.json({ status: 'OK', data: session });
    } catch (err) {
      next(err);
    }
  },
);

cashRouter.post('/transactions', ...requireCashier, validate(recordCashTransactionSchema), async (req, res, next) => {
  try {
    const { transactionType, amount, description, referenceNumber, category } = req.body;
    const transaction = await cashService.recordTransaction(
      req.staff!.restaurantId!,
      req.staff!.id,
      transactionType,
      amount,
      description,
      referenceNumber,
      category,
    );
    res.status(201).json({ status: 'OK', data: transaction });
  } catch (err) {
    next(err);
  }
});
