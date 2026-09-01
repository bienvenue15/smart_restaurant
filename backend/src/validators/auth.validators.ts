import Joi from 'joi';

export const staffLoginSchema = Joi.object({
  username: Joi.string().trim().min(3).max(200).required(),
  password: Joi.string().min(6).max(100).required(),
});

export const forgotPasswordSchema = Joi.object({
  identifier: Joi.string().min(3).max(200).required(),
});

export const resetPasswordSchema = Joi.object({
  token: Joi.string().min(16).max(200).required(),
  password: Joi.string().min(6).max(100).required(),
});

export const changePasswordSchema = Joi.object({
  currentPassword: Joi.string().min(6).max(100).required(),
  newPassword: Joi.string().min(6).max(100).required(),
});

export const twoFactorLoginSchema = Joi.object({
  pendingToken: Joi.string().required(),
  code: Joi.string().pattern(/^\d{6}$/).required(),
});

export const twoFactorCodeSchema = Joi.object({
  code: Joi.string().pattern(/^\d{6}$/).required(),
});

export const disableTwoFactorSchema = Joi.object({
  password: Joi.string().min(6).max(100).required(),
  code: Joi.string().pattern(/^\d{6}$/).required(),
});
