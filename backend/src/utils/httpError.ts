export class HttpError extends Error {
  constructor(
    public status: number,
    public code: string,
    message: string,
  ) {
    super(message);
    this.name = 'HttpError';
  }
}

export const Unauthorized = (message = 'Authentication required') => new HttpError(401, 'UNAUTHORIZED', message);
export const Forbidden = (message = 'You do not have permission to perform this action') =>
  new HttpError(403, 'FORBIDDEN', message);
export const NotFound = (message = 'Resource not found') => new HttpError(404, 'NOT_FOUND', message);
export const Conflict = (message: string) => new HttpError(409, 'CONFLICT', message);
export const BadRequest = (message: string) => new HttpError(400, 'BAD_REQUEST', message);
