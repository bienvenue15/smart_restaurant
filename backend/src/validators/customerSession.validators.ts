import Joi from 'joi';

export const scanQrCodeSchema = Joi.object({
  qrCode: Joi.string().min(1).max(255).required(),
});
