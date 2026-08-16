import { Router } from 'express';
import { validate } from '@/middleware/validate';
import { config } from '@/config/env';
import { computeDeviceFingerprint } from '@/utils/deviceFingerprint';
import { scanQrCodeSchema } from '@/validators/customerSession.validators';
import * as customerSessionService from './customerSession.service';

export const customerSessionRouter = Router();

customerSessionRouter.post('/scan', validate(scanQrCodeSchema), async (req, res, next) => {
  try {
    const deviceFingerprint = computeDeviceFingerprint(req);
    const result = await customerSessionService.scanQrCode(req.body.qrCode, deviceFingerprint);

    res.cookie('sr_customer_session', result.sessionToken, {
      httpOnly: true,
      secure: config.nodeEnv === 'production',
      sameSite: 'lax',
      maxAge: 2 * 60 * 60 * 1000,
    });

    res.json({ status: 'OK', data: result });
  } catch (err) {
    next(err);
  }
});
