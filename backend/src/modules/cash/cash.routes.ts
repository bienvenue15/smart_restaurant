import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { requireActiveShift } from '@/middleware/shift';
import { validate } from '@/middleware/validate';
import { closeCashSessionSchema, openCashSessionSchema, recordCashTransactionSchema } from '@/validators/cash.validators';
import * as cashService from './cash.service';

export const cashRouter = Router();
cashRouter.use(requireStaffAuth, requireActiveShift, requirePermission('handle_cash'));

cashRouter.post('/sessions', validate(openCashSessionSchema), async (req, res, next) => {
  try {
    const session = await cashService.openSession(req.staff!.restaurantId!, req.staff!.id, req.body.openingBalance);
    res.status(201).json({ status: 'OK', data: session });
  } catch (err) {
    next(err);
  }
});

cashRouter.get('/sessions/current', async (req, res, next) => {
  try {
    const session = await cashService.getCurrentSession(req.staff!.id);
    res.json({ status: 'OK', data: session });
  } catch (err) {
    next(err);
  }
});

cashRouter.get('/sessions/history', async (req, res, next) => {
  try {
    const sessions = await cashService.getHistory(req.staff!.restaurantId!, req.staff!.id);
    res.json({ status: 'OK', data: sessions });
  } catch (err) {
    next(err);
  }
});

cashRouter.post('/sessions/:id/close', validate(closeCashSessionSchema), async (req, res, next) => {
  try {
    const session = await cashService.closeSession(req.staff!.restaurantId!, req.params.id!, req.staff!.id, req.body.closingBalance);
    res.json({ status: 'OK', data: session });
  } catch (err) {
    next(err);
  }
});

cashRouter.post('/transactions', validate(recordCashTransactionSchema), async (req, res, next) => {
  try {
    const { transactionType, amount, description, referenceNumber } = req.body;
    const transaction = await cashService.recordTransaction(req.staff!.restaurantId!, req.staff!.id, transactionType, amount, description, referenceNumber);
    res.status(201).json({ status: 'OK', data: transaction });
  } catch (err) {
    next(err);
  }
});
