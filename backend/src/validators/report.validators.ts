import Joi from 'joi';

export const dateRangeSchema = Joi.object({
  startDate: Joi.date().iso().required(),
  endDate: Joi.date().iso().min(Joi.ref('startDate')).required(),
  limit: Joi.number().integer().min(1).max(100).default(10),
});

export const profitLossSchema = Joi.object({
  period: Joi.string().valid('daily', 'weekly', 'monthly').default('daily'),
});
