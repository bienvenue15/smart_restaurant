import { NextFunction, Request, Response } from 'express';
import { assertFeature, assertSubscriptionActive, type EnforceableFeature } from '@/modules/subscriptions/subscription.service';
import { Unauthorized } from '@/utils/httpError';

export function requireActiveSubscription() {
  return async (req: Request, _res: Response, next: NextFunction): Promise<void> => {
    if (!req.staff) return next(Unauthorized());
    if (!req.staff.restaurantId || req.staff.role === 'SUPER_ADMIN') return next();
    try {
      await assertSubscriptionActive(req.staff.restaurantId);
      next();
    } catch (err) {
      next(err);
    }
  };
}

export function requireFeature(code: EnforceableFeature) {
  return async (req: Request, _res: Response, next: NextFunction): Promise<void> => {
    const restaurantId = req.staff?.restaurantId ?? req.customerSession?.restaurantId;
    if (!restaurantId) {
      if (req.staff?.role === 'SUPER_ADMIN') return next();
      return next(Unauthorized());
    }
    try {
      await assertFeature(restaurantId, code);
      next();
    } catch (err) {
      next(err);
    }
  };
}
