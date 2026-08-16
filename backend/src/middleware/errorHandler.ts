import { NextFunction, Request, Response } from 'express';
import { HttpError } from '@/utils/httpError';
import { logger } from '@/utils/logger';

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export function errorHandler(err: unknown, req: Request, res: Response, _next: NextFunction): void {
  if (err instanceof HttpError) {
    res.status(err.status).json({ status: 'FAIL', code: err.code, message: err.message });
    return;
  }

  if (err && typeof err === 'object' && 'isJoi' in err) {
    const joiErr = err as unknown as { details: { message: string }[] };
    res.status(400).json({
      status: 'FAIL',
      code: 'VALIDATION_ERROR',
      message: joiErr.details.map((d) => d.message).join('; '),
    });
    return;
  }

  logger.error({ err, path: req.path, method: req.method }, 'Unhandled error');
  res.status(500).json({ status: 'FAIL', code: 'INTERNAL_ERROR', message: 'Something went wrong' });
}
