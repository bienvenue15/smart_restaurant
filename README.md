# Smart Restaurant

QR table ordering, kitchen display, waiter/cashier floor, and a platform admin console. Guests do not pay in the app — staff record cash, card, or mobile money.

Stack: Nuxt 4 → Express `/api/v1` → Prisma → PostgreSQL.

| Who | URL |
|---|---|
| Marketing / trial | `/` `/register` |
| Guest menu | `/menu/<qr-token>` |
| Staff | `/staff/login` |
| Platform admin | same login; `SUPER_ADMIN` lands on `/admin` |

Languages: English, French, Kinyarwanda, Kiswahili.

**Do not promise:** in-app MoMo/card checkout, automatic inventory, or multi-location ERP. Those labels on plans are limits/tags, not modules.

## Production

See **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)**.

```bash
cp .env.example .env   # fill secrets — never commit .env
docker compose up --build -d
docker compose exec backend npx prisma db seed
```

Open `http://<host>/health` → `{ "status": "OK" }`. Put TLS in front (sample in `docker/nginx.conf.example`).

## Local development

```bash
cp .env.example .env   # JWT_ACCESS_SECRET and JWT_REFRESH_SECRET required

cd backend && npm install && npm run prisma:migrate && npm run prisma:seed && npm run dev
cd frontend && npm install && npm run dev
```

API: http://localhost:4000 · site: http://localhost:3000
