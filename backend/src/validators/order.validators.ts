import Joi from 'joi';

// Deliberately does NOT accept a `price` field from the client — see
// order.service.ts::createOrder for why (docs/SECURITY_AUDIT.md #2).
export const createOrderSchema = Joi.object({
  tableId: Joi.string().uuid().required(),
  items: Joi.array()
    .items(
      Joi.object({
        menuItemId: Joi.string().uuid().required(),
        quantity: Joi.number().integer().min(1).max(100).required(),
        specialRequest: Joi.string().max(200).allow('', null),
      }),
    )
    .min(1)
    .required(),
  specialInstructions: Joi.string().max(500).allow('', null),
});

export const updateOrderStatusSchema = Joi.object({
  status: Joi.string().valid('CONFIRMED', 'COMPLETED').required(),
});

export const updateOrderItemStatusSchema = Joi.object({
  status: Joi.string().valid('PREPARING', 'READY', 'SERVED').required(),
});

export const recordPaymentSchema = Joi.object({
  amount: Joi.number().min(0.01).max(9_999_999).precision(2).required(),
  receivedAmount: Joi.number().min(0).max(9_999_999).precision(2).required(),
  paymentMethod: Joi.string().valid('CASH', 'CARD', 'MOBILE_MONEY', 'BANK_TRANSFER').required(),
  paymentReference: Joi.string().max(100).allow('', null),
});
