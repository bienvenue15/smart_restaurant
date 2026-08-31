import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { uploadImage } from '@/middleware/upload';
import { validate } from '@/middleware/validate';
import { BadRequest } from '@/utils/httpError';
import { registerRestaurantSchema, updateRestaurantSchema } from '@/validators/restaurant.validators';
import * as restaurantService from './restaurant.service';
import { saveRestaurantLogo } from '@/modules/uploads/upload.service';

export const publicRestaurantRouter = Router();

publicRestaurantRouter.post('/register', validate(registerRestaurantSchema), async (req, res, next) => {
  try {
    const result = await restaurantService.registerRestaurant(req.body);
    res.status(201).json({ status: 'OK', data: result });
  } catch (err) {
    next(err);
  }
});

export const staffRestaurantRouter = Router();
staffRestaurantRouter.use(requireStaffAuth);

staffRestaurantRouter.get('/me', requirePermission('manage_settings'), async (req, res, next) => {
  try {
    const restaurant = await restaurantService.getRestaurant(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: restaurant });
  } catch (err) {
    next(err);
  }
});

staffRestaurantRouter.patch('/me', requirePermission('manage_settings'), validate(updateRestaurantSchema), async (req, res, next) => {
  try {
    const restaurant = await restaurantService.updateRestaurant(req.staff!.restaurantId!, req.body);
    res.json({ status: 'OK', data: restaurant });
  } catch (err) {
    next(err);
  }
});

staffRestaurantRouter.post('/me/logo', requirePermission('manage_settings'), uploadImage, async (req, res, next) => {
  try {
    if (!req.file) throw BadRequest('No image file provided (field name: "image")');
    const logoUrl = await saveRestaurantLogo(req.staff!.restaurantId!, req.file.buffer);
    const restaurant = await restaurantService.updateRestaurant(req.staff!.restaurantId!, { logoUrl });
    res.json({ status: 'OK', data: restaurant });
  } catch (err) {
    next(err);
  }
});
