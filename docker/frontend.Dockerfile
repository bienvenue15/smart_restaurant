# syntax=docker/dockerfile:1
FROM node:22-slim AS base
WORKDIR /app

FROM base AS deps
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm install

FROM deps AS build
COPY frontend/ .
RUN npm run build

FROM node:22-slim AS runtime
WORKDIR /app
ENV NODE_ENV=production
RUN addgroup --system --gid 1001 nodejs && adduser --system --uid 1001 nuxtjs
COPY --from=build /app/.output ./.output
USER nuxtjs
EXPOSE 3000
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD node -e "require('http').get('http://localhost:3000', r => process.exit(r.statusCode < 500 ? 0 : 1)).on('error', () => process.exit(1))"
CMD ["node", ".output/server/index.mjs"]
