import Joi from 'joi';

export const createWaiterCallSchema = Joi.object({
  requestType: Joi.string().valid('ORDER', 'ASSISTANCE', 'BILL', 'COMPLAINT', 'OTHER').required(),
  message: Joi.string().max(500).allow('', null),
  priority: Joi.string().valid('LOW', 'NORMAL', 'HIGH').default('NORMAL'),
});

export const assignWaiterCallSchema = Joi.object({
  staffId: Joi.string().uuid().required(),
});
