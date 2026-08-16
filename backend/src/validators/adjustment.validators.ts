import Joi from 'joi';

export const requestDiscountSchema = Joi.object({
  discountPercent: Joi.number().min(0.01).max(100).required(),
  reason: Joi.string().min(3).max(500).required(),
});

export const requestRefundSchema = Joi.object({
  amount: Joi.number().min(0.01).max(9_999_999).precision(2).required(),
  reason: Joi.string().min(3).max(500).required(),
});
