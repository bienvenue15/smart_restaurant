import Joi from 'joi';

export const liabilityReasonSchema = Joi.object({
  reason: Joi.string().min(3).max(500).required(),
});
