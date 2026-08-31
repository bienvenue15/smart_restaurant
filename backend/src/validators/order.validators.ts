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

// Deliberately does NOT accept a `price` field from the client, same
// reasoning as createOrderSchema above — this is the direct fix for the
// second half of SECURITY_AUDIT.md #2 (legacy's `add_items_to_order`
// trusted client-supplied item prices exactly like `create_order` did).
export const addOrderItemsSchema = Joi.object({
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
});

export const updateOrderStatusSchema = Joi.object({
  status: Joi.string().valid('CONFIRMED', 'COMPLETED').required(),
});

export const updateOrderItemStatusSchema = Joi.object({
  status: Joi.string().valid('PREPARING', 'READY', 'SERVED').required(),
});

export const assignOrderSchema = Joi.object({
  staffId: Joi.string().uuid().required(),
});

export const recordPaymentSchema = Joi.object({
  amount: Joi.number().min(0.01).max(9_999_999).precision(2).required(),
  receivedAmount: Joi.number().min(0).max(9_999_999).precision(2).required(),
  paymentMethod: Joi.string().valid('CASH', 'CARD', 'MOBILE_MONEY', 'BANK_TRANSFER').required(),
  paymentReference: Joi.string().max(100).allow('', null),
});

export const guestHeardAboutSchema = Joi.object({
  skipped: Joi.boolean().default(false),
  channel: Joi.string()
    .valid('WALK_IN', 'GOOGLE', 'SOCIAL', 'FRIEND', 'HOTEL', 'EVENT', 'OTHER')
    .allow(null),
  rating: Joi.number().integer().min(1).max(5).allow(null),
  comment: Joi.string().trim().max(500).allow('', null),
}).custom((value, helpers) => {
  if (value.skipped) return { skipped: true, channel: null, rating: null, comment: null };
  if (!value.rating) return helpers.error('any.custom', { message: 'Pick a rating or skip' });
  const comment = typeof value.comment === 'string' && value.comment.trim() ? value.comment.trim() : null;
  return { skipped: false, channel: value.channel ?? null, rating: value.rating, comment };
});
