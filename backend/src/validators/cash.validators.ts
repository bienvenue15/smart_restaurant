import Joi from 'joi';

export const openCashSessionSchema = Joi.object({
  openingBalance: Joi.number().min(0).max(99_999_999).precision(2).required(),
});

export const closeCashSessionSchema = Joi.object({
  closingBalance: Joi.number().min(0).max(99_999_999).precision(2).required(),
});

export const recordCashTransactionSchema = Joi.object({
  transactionType: Joi.string().valid('SALE', 'REFUND', 'EXPENSE', 'DEPOSIT', 'WITHDRAWAL', 'ADJUSTMENT').required(),
  amount: Joi.number().min(0.01).max(99_999_999).precision(2).required(),
  description: Joi.string().max(500).allow('', null),
  referenceNumber: Joi.string().max(100).allow('', null),
});
