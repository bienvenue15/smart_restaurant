import Joi from 'joi';

export const createRestaurantByAdminSchema = Joi.object({
  restaurantName: Joi.string().min(2).max(200).required(),
  ownerName: Joi.string().min(2).max(100).required(),
  email: Joi.string().email().required(),
  phone: Joi.string().max(20).allow('', null),
  tin: Joi.string()
    .pattern(/^\d{9,10}$/)
    .allow('', null),
  address: Joi.string().max(500).allow('', null),
  city: Joi.string().max(100).default('Kigali'),
  subscriptionPlan: Joi.string().valid('TRIAL', 'BASIC', 'PREMIUM', 'ENTERPRISE').default('TRIAL'),
});

export const extendSubscriptionSchema = Joi.object({
  additionalDays: Joi.number().integer().min(1).max(3650).required(),
});

export const hardDeleteRestaurantSchema = Joi.object({
  confirmSlug: Joi.string().min(1).max(200).required(),
});

export const assignRestaurantPlanSchema = Joi.object({
  subscriptionPlan: Joi.string().valid('TRIAL', 'BASIC', 'PREMIUM', 'ENTERPRISE').required(),
});

export const toggleRestaurantStatusSchema = Joi.object({
  isActive: Joi.boolean().required(),
});

export const togglePlatformUserStatusSchema = Joi.object({
  isActive: Joi.boolean().required(),
});

export const updateSystemSettingSchema = Joi.object({
  value: Joi.string().max(5000).required(),
  description: Joi.string().max(500).allow('', null),
});

export const updateSubscriptionPlanSchema = Joi.object({
  displayName: Joi.string().max(100),
  priceMonthly: Joi.number().min(0),
  priceYearly: Joi.number().min(0),
  maxTables: Joi.number().integer().min(0),
  maxUsers: Joi.number().integer().min(0),
  maxMenuItems: Joi.number().integer().min(0),
  maxOrdersPerMonth: Joi.number().integer().min(0),
  features: Joi.array().items(Joi.string().max(50)),
  isActive: Joi.boolean(),
});
