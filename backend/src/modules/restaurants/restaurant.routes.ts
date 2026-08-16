import { Router } from 'express';
import { requireStaffAuth } from '@/middleware/auth';
import { requirePermission } from '@/middleware/permission';
import { validate } from '@/middleware/validate';
import { registerRestaurantSchema, updateRestaurantSchema, updateSettingSchema } from '@/validators/restaurant.validators';
import * as restaurantService from './restaurant.service';

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

staffRestaurantRouter.get('/me/settings', requirePermission('manage_settings'), async (req, res, next) => {
  try {
    const settings = await restaurantService.getSettings(req.staff!.restaurantId!);
    res.json({ status: 'OK', data: settings });
  } catch (err) {
    next(err);
  }
});

staffRestaurantRouter.put('/me/settings', requirePermission('manage_settings'), validate(updateSettingSchema), async (req, res, next) => {
  try {
    const setting = await restaurantService.setSetting(req.staff!.restaurantId!, req.body.settingKey, req.body.settingValue ?? null);
    res.json({ status: 'OK', data: setting });
  } catch (err) {
    next(err);
  }
});
