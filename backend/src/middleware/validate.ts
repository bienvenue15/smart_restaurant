import { NextFunction, Request, Response } from 'express';
import { ObjectSchema } from 'joi';

type Source = 'body' | 'query' | 'params';

/**
 * Joi-based request validation, applied at the route layer before any
 * service/Prisma call. The legacy app validated inconsistently across
 * ApiValidator/ValidationHelper with different rules duplicated per
 * controller (docs/CURRENT_SYSTEM_AUDIT.md) — every route here declares
 * its schema explicitly and validation always runs first.
 */
export function validate(schema: ObjectSchema, source: Source = 'body') {
  return (req: Request, res: Response, next: NextFunction): void => {
    const { error, value } = schema.validate(req[source], { abortEarly: false, stripUnknown: true });
    if (error) return next(error);
    req[source] = value;
    next();
  };
}
