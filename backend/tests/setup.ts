process.env.DATABASE_URL ??= 'postgresql://test:test@localhost:5432/test';
process.env.JWT_ACCESS_SECRET ??= 'test-access-secret-at-least-32-chars-long!!';
process.env.JWT_REFRESH_SECRET ??= 'test-refresh-secret-at-least-32-chars-long!';
