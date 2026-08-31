# Production deployment

One public hostname. Nginx (or your TLS proxy) terminates HTTPS and routes `/` to the Nuxt app and `/api/` + `/uploads/` to Express. PostgreSQL is not published to the internet.

## 1. Secrets

Copy `.env.example` to `.env` on the host. Never commit `.env`.

Generate:

```bash
openssl rand -base64 48   # JWT_ACCESS_SECRET
openssl rand -base64 48   # JWT_REFRESH_SECRET
openssl rand -base64 24   # POSTGRES_PASSWORD
openssl rand -base64 24   # SUPERADMIN_PASSWORD
```

Required:

| Variable | Rule |
|---|---|
| `POSTGRES_PASSWORD` | Unique, not `changeme` |
| `JWT_ACCESS_SECRET` / `JWT_REFRESH_SECRET` | ≥ 32 characters, unique |
| `SUPERADMIN_PASSWORD` | Not `ChangeMe123!` (seed refuses it when `NODE_ENV=production`) |
| `CORS_ORIGIN` | Public site origin, e.g. `https://your.domain` — never `*` |
| `FRONTEND_URL` | Same origin as the site (password-reset links) |
| `NUXT_PUBLIC_API_BASE_URL` | `/api/v1` when Nginx proxies same-origin |

If you still have the old PHP app in git history, rotate the production MySQL and SMTP passwords before cutover. This stack has no hardcoded secret fallbacks; missing JWT or database URL prevents boot.

Mail / SMS / WhatsApp stay off until `MAIL_DISABLE_DELIVERY=false` (and the SMS/WhatsApp flags) plus credentials.

## 2. Start

From the repo root, with Docker installed:

```bash
docker compose up --build -d
docker compose exec backend npx prisma db seed
docker compose ps
curl -sS http://127.0.0.1/health
```

Expected: `{ "status": "OK" }`. Seed once on an empty database.

Compose binds **port 80** (Nginx). Postgres, the API, and Nuxt stay on the internal network. Menu images persist on the `backend_uploads` volume; dumps on `backend_backups`.

To try Compose on this machine without TLS, set `CORS_ORIGIN=http://localhost` and `FRONTEND_URL=http://localhost` (the site is port 80, not 3000).

Put TLS in front with `docker/nginx.conf.example` (Let’s Encrypt on the host, or a load balancer). SSE needs `proxy_buffering off` on `/api/` — the bundled HTTP Nginx config already does this.

## 3. After first boot

1. Log in at `/staff/login` with `SUPERADMIN_USERNAME` / `SUPERADMIN_PASSWORD`.
2. Enable 2FA on that account (`/admin` → account/settings).
3. Create or import a restaurant. Set real plan prices on `/admin/plans` before you quote customers.
4. Test SMTP with `MAIL_DISABLE_DELIVERY=false` (one support or contact message).
5. Toggle maintenance mode on/off from `/admin/settings`.
6. Run a backup from `/admin/settings` and download the dump.

Staff mutating actions require clock-in. Kitchen staff land on the prep board. A second phone cannot scan a table that already has a live order (device–table lock, ~2 hours).

## 4. Data cutover (existing MySQL tenants)

Schema is already PostgreSQL. Live restaurant data stays on MySQL until:

```bash
cd backend
MYSQL_CUTOVER_URL=mysql://user:pass@host:3306/dbname npm run cutover:mysql
MYSQL_CUTOVER_URL=... npm run cutover:mysql -- --apply
```

Dry-run first, against a snapshot, not live traffic. Printed QR tokens are preserved. Then `pg_dump` (Admin → Settings → Backup). Keep the old PHP vhost until one busy service has run on the new stack.

Do not re-run `--apply` on a database that already contains imported tenants.

## 5. Rollback

1. Point the reverse proxy back at the previous PHP vhost if it is still up.
2. Restore PostgreSQL from the last `pg_dump` if the new API already wrote data you must keep.
3. After the soak window: previous Docker images + dump restore only.

## 6. Checklist

- [ ] JWT secrets and superadmin password are unique
- [ ] `CORS_ORIGIN` / `FRONTEND_URL` are the public HTTPS origin
- [ ] `docker compose ps` healthy; `/health` returns OK
- [ ] Seed ran once; superadmin can log in; 2FA on
- [ ] SMTP tested if you need password reset / support mail
- [ ] Cutover dry-run then `--apply` if you have MySQL tenants; QRs still open
- [ ] Backup download works
- [ ] TLS live; guest scan + waiter confirm + kitchen + cashier payment on a trial table
