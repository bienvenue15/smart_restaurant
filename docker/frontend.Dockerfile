# syntax=docker/dockerfile:1
FROM node:22-slim AS base
WORKDIR /app

FROM base AS deps
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm ci

FROM deps AS build
ARG API_PROXY_TARGET=http://backend:4000
ARG NUXT_PUBLIC_API_BASE_URL=/api/v1
ENV API_PROXY_TARGET=$API_PROXY_TARGET
ENV NUXT_PUBLIC_API_BASE_URL=$NUXT_PUBLIC_API_BASE_URL
ENV NODE_ENV=production
COPY frontend/ .
RUN npm run build

FROM node:22-slim AS runtime
WORKDIR /app
ENV NODE_ENV=production
ENV NUXT_PUBLIC_API_BASE_URL=/api/v1
ENV NUXT_API_ORIGIN=http://backend:4000
RUN addgroup --system --gid 1001 nodejs && adduser --system --uid 1001 nuxtjs
COPY --from=build /app/.output ./.output
USER nuxtjs
EXPOSE 3000
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
  CMD node -e "require('http').get('http://127.0.0.1:3000', r => process.exit(r.statusCode < 500 ? 0 : 1)).on('error', () => process.exit(1))"
CMD ["node", ".output/server/index.mjs"]
