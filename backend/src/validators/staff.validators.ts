import Joi from 'joi';

const ROLES = ['ADMIN', 'MANAGER', 'WAITER', 'KITCHEN', 'CASHIER'];

export const createStaffSchema = Joi.object({
  username: Joi.string().alphanum().min(3).max(50).required(),
  password: Joi.string().min(6).max(100).required(),
  fullName: Joi.string().min(1).max(100).required(),
  email: Joi.string().email().allow('', null),
  phone: Joi.string().max(20).allow('', null),
  role: Joi.string()
    .valid(...ROLES)
    .required(),
  canHandleCash: Joi.boolean().default(false),
  maxDiscountPercent: Joi.number().min(0).max(100).default(0),
});

export const updateStaffSchema = Joi.object({
  fullName: Joi.string().min(1).max(100),
  email: Joi.string().email().allow('', null),
  phone: Joi.string().max(20).allow('', null),
  role: Joi.string().valid(...ROLES),
  isActive: Joi.boolean(),
  canHandleCash: Joi.boolean(),
  maxDiscountPercent: Joi.number().min(0).max(100),
});
