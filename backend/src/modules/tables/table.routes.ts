import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { validate } from '@/middleware/validate';
import { createTableSchema, updateTableSchema } from '@/validators/table.validators';
import * as tableService from './table.service';

export const staffTableRouter = Router();
staffTableRouter.use(requireStaffAuth);

staffTableRouter.get('/', requirePermission('view_tables'), async (req, res, next) => {
  try {
    const tables = await tableService.listTables(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: tables });
  } catch (err) {
    next(err);
  }
});

staffTableRouter.post('/', requirePermission('manage_tables'), validate(createTableSchema), async (req, res, next) => {
  try {
    const table = await tableService.createTable(req.staff!.restaurantId!, req.body);
    res.status(201).json({ status: 'OK', data: table });
  } catch (err) {
    next(err);
  }
});

staffTableRouter.patch('/:id', requirePermission('manage_tables'), validate(updateTableSchema), async (req, res, next) => {
  try {
    const table = await tableService.updateTable(req.staff!.restaurantId!, req.params.id!, req.body);
    res.json({ status: 'OK', data: table });
  } catch (err) {
    next(err);
  }
});

staffTableRouter.delete('/:id', requirePermission('manage_tables'), async (req, res, next) => {
  try {
    await tableService.deleteTable(req.staff!.restaurantId!, req.params.id!);
    res.status(204).send();
  } catch (err) {
    next(err);
  }
});

staffTableRouter.post('/:id/reset', requirePermission('reset_table'), async (req, res, next) => {
  try {
    const table = await tableService.resetTable(req.staff!.restaurantId!, req.params.id!, req.staff!.id);
    res.json({ status: 'OK', data: table });
  } catch (err) {
    next(err);
  }
});

staffTableRouter.post('/:id/regenerate-qr', requirePermission('manage_tables'), async (req, res, next) => {
  try {
    const table = await tableService.regenerateQrCode(req.staff!.restaurantId!, req.params.id!);
    res.json({ status: 'OK', data: table });
  } catch (err) {
    next(err);
  }
});
