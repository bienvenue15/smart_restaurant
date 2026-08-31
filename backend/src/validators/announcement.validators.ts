import Joi from 'joi';

export const createAnnouncementSchema = Joi.object({
  title: Joi.string().min(2).max(200).required(),
  message: Joi.string().min(2).max(2000).required(),
  type: Joi.string().valid('INFO', 'WARNING', 'SUCCESS', 'DANGER', 'PROMOTION').default('INFO'),
  targetAudience: Joi.string().valid('ALL', 'STAFF', 'CUSTOMERS', 'ADMINS').default('ALL'),
  priority: Joi.string().valid('LOW', 'NORMAL', 'HIGH', 'URGENT').default('NORMAL'),
  restaurantId: Joi.string().uuid().allow(null),
  isActive: Joi.boolean().default(true),
  isDismissible: Joi.boolean().default(true),
  startDate: Joi.date().iso().allow(null),
  endDate: Joi.date().iso().allow(null),
});

export const updateAnnouncementSchema = Joi.object({
  title: Joi.string().min(2).max(200),
  message: Joi.string().min(2).max(2000),
  type: Joi.string().valid('INFO', 'WARNING', 'SUCCESS', 'DANGER', 'PROMOTION'),
  targetAudience: Joi.string().valid('ALL', 'STAFF', 'CUSTOMERS', 'ADMINS'),
  priority: Joi.string().valid('LOW', 'NORMAL', 'HIGH', 'URGENT'),
  restaurantId: Joi.string().uuid().allow(null),
  isActive: Joi.boolean(),
  isDismissible: Joi.boolean(),
  startDate: Joi.date().iso().allow(null),
  endDate: Joi.date().iso().allow(null),
}).min(1);
