import Joi from 'joi';
import { CASHIER_LEDGER_TYPES, EXPENSE_CATEGORIES } from '@/modules/cash/cash.constants';

export const openCashSessionSchema = Joi.object({
  openingBalance: Joi.number().min(0).max(99_999_999).precision(2).required(),
});

export const closeCashSessionSchema = Joi.object({
  closingBalance: Joi.number().min(0).max(99_999_999).precision(2).required(),
});

export const recordCashTransactionSchema = Joi.object({
  transactionType: Joi.string()
    .valid(...CASHIER_LEDGER_TYPES)
    .required(),
  amount: Joi.number().min(0.01).max(99_999_999).precision(2).required(),
  category: Joi.string()
    .valid(...EXPENSE_CATEGORIES)
    .when('transactionType', { is: 'EXPENSE', then: Joi.required(), otherwise: Joi.allow('', null) }),
  description: Joi.alternatives().conditional('transactionType', {
    is: Joi.valid('EXPENSE', 'WITHDRAWAL'),
    then: Joi.string().trim().min(3).max(500).required(),
    otherwise: Joi.string().trim().max(500).allow('', null),
  }),
  referenceNumber: Joi.string().max(100).allow('', null),
});
