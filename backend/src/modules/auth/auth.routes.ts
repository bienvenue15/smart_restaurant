import { Router, Response } from 'express';
import { validate } from '@/middleware/validate';
import { requireStaffAuth } from '@/middleware/auth';
import { requireSuperAdmin } from '@/middleware/superAdmin';
import { config } from '@/config/env';
import { Unauthorized } from '@/utils/httpError';
import {
  changePasswordSchema,
  disableTwoFactorSchema,
  forgotPasswordSchema,
  resetPasswordSchema,
  staffLoginSchema,
  twoFactorCodeSchema,
  twoFactorLoginSchema,
} from '@/validators/auth.validators';
import * as authService from './auth.service';

export const authRouter = Router();

const REFRESH_COOKIE_NAME = 'sr_staff_refresh';
const REFRESH_COOKIE_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000;

/**
 * The refresh token never reaches client JS — it only ever travels as this
 * httpOnly cookie (see useAuthStore.ts on the frontend for the matching
 * in-memory-access-token half of this design).
 */
function setRefreshCookie(res: Response, token: string) {
  res.cookie(REFRESH_COOKIE_NAME, token, {
    httpOnly: true,
    secure: config.nodeEnv === 'production',
    sameSite: 'lax',
    path: '/',
    maxAge: REFRESH_COOKIE_MAX_AGE_MS,
  });
}

function clearRefreshCookie(res: Response) {
  res.clearCookie(REFRESH_COOKIE_NAME, { path: '/' });
}

authRouter.post('/login', validate(staffLoginSchema), async (req, res, next) => {
  try {
    const { username, password } = req.body;
    const result = await authService.login(username, password);
    if (result.requiresTwoFactor) {
      res.json({ status: 'OK', data: result });
      return;
    }
    setRefreshCookie(res, result.refreshToken);
    const { refreshToken: _refreshToken, ...body } = result;
    res.json({ status: 'OK', data: body });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/refresh', async (req, res, next) => {
  try {
    const refreshToken = req.cookies?.[REFRESH_COOKIE_NAME];
    if (!refreshToken) throw Unauthorized('No refresh session found — sign in again');
    const result = await authService.refresh(refreshToken);
    setRefreshCookie(res, result.refreshToken);
    res.json({ status: 'OK', data: { accessToken: result.accessToken, staff: result.staff } });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/logout', requireStaffAuth, async (req, res, next) => {
  try {
    await authService.logout(req.staff!.id);
    clearRefreshCookie(res);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/forgot-password', validate(forgotPasswordSchema), async (req, res, next) => {
  try {
    await authService.requestPasswordReset(req.body.identifier);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/reset-password', validate(resetPasswordSchema), async (req, res, next) => {
  try {
    await authService.resetPassword(req.body.token, req.body.password);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/change-password', requireStaffAuth, validate(changePasswordSchema), async (req, res, next) => {
  try {
    await authService.changePassword(req.staff!.id, req.body.currentPassword, req.body.newPassword);
    res.json({ status: 'OK' });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/login/2fa', validate(twoFactorLoginSchema), async (req, res, next) => {
  try {
    const result = await authService.completeTwoFactorLogin(req.body.pendingToken, req.body.code);
    setRefreshCookie(res, result.refreshToken);
    const { refreshToken: _refreshToken, ...body } = result;
    res.json({ status: 'OK', data: body });
  } catch (err) {
    next(err);
  }
});

authRouter.get('/2fa', requireStaffAuth, requireSuperAdmin, async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await authService.getTwoFactorStatus(req.staff!.id) });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/2fa/setup', requireStaffAuth, requireSuperAdmin, async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await authService.setupTwoFactor(req.staff!.id) });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/2fa/cancel', requireStaffAuth, requireSuperAdmin, async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await authService.cancelTwoFactorSetup(req.staff!.id) });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/2fa/enable', requireStaffAuth, requireSuperAdmin, validate(twoFactorCodeSchema), async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await authService.enableTwoFactor(req.staff!.id, req.body.code) });
  } catch (err) {
    next(err);
  }
});

authRouter.post('/2fa/disable', requireStaffAuth, requireSuperAdmin, validate(disableTwoFactorSchema), async (req, res, next) => {
  try {
    res.json({ status: 'OK', data: await authService.disableTwoFactor(req.staff!.id, req.body.password, req.body.code) });
  } catch (err) {
    next(err);
  }
});
