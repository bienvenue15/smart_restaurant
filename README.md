# Smart Restaurant

This repository currently contains **two systems**:

1. **The legacy PHP application** (repo root: `app/`, `api/`, `src/`, `assets/`, `index.php`, etc.) — the live, production system currently serving [smartresto.inovasiyo.rw](https://smartresto.inovasiyo.rw). This is the **source of truth for business functionality** during the migration and is not being modified.
2. **The new platform** (`frontend/`, `backend/`, `docker/`) — a from-scratch rebuild on Nuxt 4 + Express + Prisma + PostgreSQL, under active development. See `docs/MIGRATION_PROGRESS.md` for current status.

## Start here

- [`docs/CURRENT_SYSTEM_AUDIT.md`](docs/CURRENT_SYSTEM_AUDIT.md) — full forensic audit of the legacy application (architecture, database, auth, features, known bugs, integrations).
- [`docs/SECURITY_AUDIT.md`](docs/SECURITY_AUDIT.md) — security findings on the legacy system, including one **critical, migration-independent action item** (hardcoded production credentials — see §1).
- [`docs/TARGET_ARCHITECTURE.md`](docs/TARGET_ARCHITECTURE.md) — the new stack's design.
- [`docs/DATABASE_MIGRATION_PLAN.md`](docs/DATABASE_MIGRATION_PLAN.md) — legacy MySQL schema → new PostgreSQL/Prisma schema mapping.
- [`docs/FEATURE_PARITY_CHECKLIST.md`](docs/FEATURE_PARITY_CHECKLIST.md) — per-feature migration status.
- [`docs/MIGRATION_PROGRESS.md`](docs/MIGRATION_PROGRESS.md) — phase-by-phase progress log.

## New stack — local development

```bash
# Backend
cd backend
cp ../.env.example ../.env   # fill in real values, especially JWT secrets
npm install
npm run prisma:migrate
npm run prisma:seed
npm run dev                  # http://localhost:4000

# Frontend (separate terminal)
cd frontend
npm install
npm run dev                  # http://localhost:3000
```

Or via Docker Compose (requires Docker):

```bash
docker compose up --build
```

## Legacy application

The legacy PHP app has its own `.env`-driven configuration (`src/config.php`) and runs under Apache with `mod_rewrite` (see `.htaccess`). It is left untouched by this migration until feature parity is verified — see `docs/FEATURE_PARITY_CHECKLIST.md`.

**Note:** the root `.env.example` in this repository now describes the *new* stack's environment variables (Docker Compose, backend, frontend). The legacy app's original `.env.example` content is preserved in git history (`git log --all -- .env.example`) — the legacy app's config primarily relies on `src/config.php` defaults, not a `.env` file, in practice.
