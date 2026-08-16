import { Router } from 'express';
import { requireCustomerSession, requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { uploadImage } from '@/middleware/upload';
import { validate } from '@/middleware/validate';
import { BadRequest } from '@/utils/httpError';
import { createCategorySchema, createMenuItemSchema, updateCategorySchema, updateMenuItemSchema } from '@/validators/menu.validators';
import * as menuService from './menu.service';
import { saveMenuItemImage } from '@/modules/uploads/upload.service';

export const publicMenuRouter = Router();

publicMenuRouter.get('/', requireCustomerSession, async (req, res, next) => {
  try {
    const menu = await menuService.getFullMenu(req.customerSession!.restaurantId);
    res.json({ status: 'OK', data: menu });
  } catch (err) {
    next(err);
  }
});

export const staffMenuRouter = Router();
staffMenuRouter.use(requireStaffAuth);

staffMenuRouter.get('/', requirePermission('view_menu'), async (req, res, next) => {
  try {
    const menu = await menuService.getFullMenu(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: menu });
  } catch (err) {
    next(err);
  }
});

staffMenuRouter.post('/categories', requirePermission('manage_menu'), validate(createCategorySchema), async (req, res, next) => {
  try {
    const category = await menuService.createCategory(req.staff!.restaurantId!, req.body);
    res.status(201).json({ status: 'OK', data: category });
  } catch (err) {
    next(err);
  }
});

staffMenuRouter.patch('/categories/:id', requirePermission('manage_menu'), validate(updateCategorySchema), async (req, res, next) => {
  try {
    const category = await menuService.updateCategory(req.staff!.restaurantId!, req.params.id!, req.body);
    res.json({ status: 'OK', data: category });
  } catch (err) {
    next(err);
  }
});

staffMenuRouter.delete('/categories/:id', requirePermission('manage_menu'), async (req, res, next) => {
  try {
    await menuService.deleteCategory(req.staff!.restaurantId!, req.params.id!);
    res.status(204).send();
  } catch (err) {
    next(err);
  }
});

staffMenuRouter.post('/items', requirePermission('manage_menu'), validate(createMenuItemSchema), async (req, res, next) => {
  try {
    const item = await menuService.createMenuItem(req.staff!.restaurantId!, req.body);
    res.status(201).json({ status: 'OK', data: item });
  } catch (err) {
    next(err);
  }
});

staffMenuRouter.patch('/items/:id', requirePermission('manage_menu'), validate(updateMenuItemSchema), async (req, res, next) => {
  try {
    const item = await menuService.updateMenuItem(req.staff!.restaurantId!, req.params.id!, req.body);
    res.json({ status: 'OK', data: item });
  } catch (err) {
    next(err);
  }
});

staffMenuRouter.post('/items/:id/image', requirePermission('manage_menu'), uploadImage, async (req, res, next) => {
  try {
    if (!req.file) throw BadRequest('No image file provided (field name: "image")');
    const imageUrl = await saveMenuItemImage(req.staff!.restaurantId!, req.file.buffer);
    const item = await menuService.setImageUrl(req.staff!.restaurantId!, req.params.id!, imageUrl);
    res.json({ status: 'OK', data: item });
  } catch (err) {
    next(err);
  }
});

staffMenuRouter.patch('/items/:id/availability', requirePermission('manage_menu'), async (req, res, next) => {
  try {
    const item = await menuService.setAvailability(req.staff!.restaurantId!, req.params.id!, Boolean(req.body.isAvailable));
    res.json({ status: 'OK', data: item });
  } catch (err) {
    next(err);
  }
});
