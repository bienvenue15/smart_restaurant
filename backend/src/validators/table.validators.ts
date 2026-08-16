import Joi from 'joi';

export const createTableSchema = Joi.object({
  tableNumber: Joi.string().min(1).max(10).required(),
  seats: Joi.number().integer().min(1).max(50).default(4),
});

export const updateTableSchema = Joi.object({
  tableNumber: Joi.string().min(1).max(10),
  seats: Joi.number().integer().min(1).max(50),
  status: Joi.string().valid('AVAILABLE', 'OCCUPIED', 'RESERVED', 'CLEANING'),
});
