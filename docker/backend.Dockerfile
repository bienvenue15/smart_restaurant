# syntax=docker/dockerfile:1
FROM node:22-slim AS base
WORKDIR /app

FROM base AS deps
COPY backend/package.json backend/package-lock.json* ./
RUN npm ci

FROM deps AS build
COPY backend/ .
RUN npx prisma generate
RUN npm run build

FROM node:22-slim AS runtime
WORKDIR /app
ENV NODE_ENV=production
RUN apt-get update \
  && apt-get install -y --no-install-recommends postgresql-client ca-certificates gosu \
  && rm -rf /var/lib/apt/lists/*
RUN addgroup --system --gid 1001 nodejs && adduser --system --uid 1001 expressjs \
  && mkdir -p /app/backups /app/uploads \
  && printf '%s\n' \
    '#!/bin/sh' \
    'set -e' \
    'mkdir -p /app/uploads /app/backups' \
    'chown -R expressjs:nodejs /app/uploads /app/backups' \
    'exec gosu expressjs sh -c "npx prisma migrate deploy && node dist/index.js"' \
    > /entrypoint.sh \
  && chmod +x /entrypoint.sh
COPY --from=deps /app/node_modules ./node_modules
COPY --from=build /app/dist ./dist
COPY --from=build /app/prisma ./prisma
COPY backend/package.json ./package.json
EXPOSE 4000
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
  CMD node -e "require('http').get('http://127.0.0.1:4000/health', r => process.exit(r.statusCode === 200 ? 0 : 1)).on('error', () => process.exit(1))"
ENTRYPOINT ["/entrypoint.sh"]
