import Joi from 'joi';

export const createCategorySchema = Joi.object({
  name: Joi.string().min(1).max(100).required(),
  description: Joi.string().max(1000).allow('', null),
  displayOrder: Joi.number().integer().min(0).default(0),
});

export const updateCategorySchema = createCategorySchema.fork(['name'], (s) => s.optional());

export const createMenuItemSchema = Joi.object({
  categoryId: Joi.string().uuid().required(),
  name: Joi.string().min(1).max(200).required(),
  description: Joi.string().max(2000).allow('', null),
  price: Joi.number().min(0).max(9_999_999).precision(2).required(),
  preparationTime: Joi.number().integer().min(1).max(180).default(15),
  isAvailable: Joi.boolean().default(true),
  isSpecial: Joi.boolean().default(false),
  dietaryInfo: Joi.string().max(255).allow('', null),
});

export const updateMenuItemSchema = createMenuItemSchema.fork(['categoryId', 'name', 'price'], (s) => s.optional());
