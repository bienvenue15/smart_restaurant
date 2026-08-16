import { Router } from 'express';
import { validate } from '@/middleware/validate';
import { requireStaffAuth } from '@/middleware/auth';
import { staffLoginSchema, refreshTokenSchema } from '@/validators/auth.validators';
import * as authService from './auth.service';

export const authRouter = Router();

authRouter.post('/login', validate(staffLoginSchema), async (req, res, next) => {
  try {
    const { username, password } = req.body;
    const result = await authService.login(username, password);
    res.json({ status: 'OK', data: result });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/refresh', validate(refreshTokenSchema), async (req, res, next) => {
  try {
    const result = await authService.refresh(req.body.refreshToken);
    res.json({ status: 'OK', data: result });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/logout', requireStaffAuth, async (req, res, next) => {
  try {
    await authService.logout(req.staff!.id);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});
