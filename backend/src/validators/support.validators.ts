import Joi from 'joi';

export const createPublicMessageSchema = Joi.object({
  contactName: Joi.string().min(2).max(100).required(),
  contactEmail: Joi.string().email().required(),
  subject: Joi.string().min(3).max(200).required(),
  message: Joi.string().min(5).max(5000).required(),
  restaurantName: Joi.string().max(200).allow('', null),
});

export const createTicketSchema = Joi.object({
  subject: Joi.string().min(3).max(200).required(),
  description: Joi.string().max(5000).allow('', null),
  priority: Joi.string().valid('LOW', 'MEDIUM', 'HIGH', 'URGENT').default('MEDIUM'),
});

export const replyTicketSchema = Joi.object({
  message: Joi.string().min(1).max(5000).required(),
});

export const updateTicketStatusSchema = Joi.object({
  status: Joi.string().valid('OPEN', 'IN_PROGRESS', 'WAITING_CUSTOMER', 'RESOLVED', 'CLOSED').required(),
  assignedTo: Joi.string().uuid().allow(null),
});
