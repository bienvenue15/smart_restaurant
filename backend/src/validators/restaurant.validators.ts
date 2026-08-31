import { PLATFORM_HEARD_ABOUT } from '@/utils/acquisition';
import Joi from 'joi';

export const registerRestaurantSchema = Joi.object({
  restaurantName: Joi.string().min(2).max(200).required(),
  ownerName: Joi.string().min(2).max(100).required(),
  email: Joi.string().email().required(),
  password: Joi.string().min(6).max(100).required(),
  phone: Joi.string()
    .pattern(/^(\+?250|0)?7\d{8}$/)
    .message('Phone number must be a valid Rwandan phone number')
    .allow('', null),
  tin: Joi.string()
    .pattern(/^\d{9,10}$/)
    .message('TIN must be 9-10 digits')
    .allow('', null),
  address: Joi.string().max(500).allow('', null),
  city: Joi.string().max(100).default('Kigali'),
  heardAboutUs: Joi.string()
    .valid(...PLATFORM_HEARD_ABOUT)
    .allow('', null),
  heardAboutNote: Joi.string().max(200).allow('', null),
  heardAboutSource: Joi.string().max(40).allow('', null),
});

export const updateRestaurantSchema = Joi.object({
  name: Joi.string().min(2).max(200),
  phone: Joi.string().max(20).allow('', null),
  address: Joi.string().max(500).allow('', null),
  city: Joi.string().max(100),
  logoUrl: Joi.string().max(500).allow('', null),
  primaryColor: Joi.string()
    .pattern(/^#[0-9a-fA-F]{6}$/)
    .allow('', null),
  secondaryColor: Joi.string()
    .pattern(/^#[0-9a-fA-F]{6}$/)
    .allow('', null),
  taxRate: Joi.number().min(0).max(100),
  serviceCharge: Joi.number().min(0).max(100),
  heardAboutUs: Joi.string()
    .valid(...PLATFORM_HEARD_ABOUT)
    .allow(null),
  heardAboutNote: Joi.string().max(200).allow('', null),
  heardAboutSkipped: Joi.boolean(),
});
