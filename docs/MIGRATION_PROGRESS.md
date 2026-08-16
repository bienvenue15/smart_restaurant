# Migration Progress Log

## Phase 0 — Audit ✅ Complete (2026-08-16)

- [x] Full forensic read of every controller, model, core class, and the MySQL schema dump.
- [x] `docs/CURRENT_SYSTEM_AUDIT.md` — architecture, DB, auth, feature inventory, business rules, known bugs, integrations.
- [x] `docs/FEATURE_PARITY_CHECKLIST.md` — every feature marked Audited.
- [x] `docs/DATABASE_MIGRATION_PLAN.md` — full table mapping, UUID-PK design decision, enum extraction, trigger reimplementation plan.
- [x] `docs/SECURITY_AUDIT.md` — 4 critical, 5 high, 5 medium findings with new-system remediations.
- [x] `docs/TARGET_ARCHITECTURE.md` — Nuxt 4 / Express / Prisma / PostgreSQL modular monolith, Joi validation, UUID PKs, JWT auth, RBAC consolidation plan.
- [x] Initialized git version control for the legacy codebase (was not previously a git repo locally) as a baseline reference snapshot. SQL dumps and `.env` deliberately left untracked (real restaurant PII / production secrets).

### Key decisions made during audit
- **UUID primary keys, not legacy auto-increment ints** — explicit instruction, also happens to be the direction the legacy team's own (incomplete) UUID migration was heading.
- **Joi for validation**, not Zod.
- **Modular monolith**, not microservices — confirmed appropriate given the legacy app's actual complexity.
- **Polling-based real-time for v1**, matching what's actually running in production today (legacy SSE is dead code) — real push (SSE via Postgres LISTEN/NOTIFY) deferred to v1.1 unless the business flags it as a launch blocker.
- **No payment gateway, no SMS/WhatsApp integration** — none exist in the legacy system; flagged as open business questions rather than assumed.

### Urgent, migration-independent action item
🔴 **Production database and SMTP credentials are hardcoded in `src/config.php` and `deploy_to_production.sh`.** Recommend rotating both immediately and checking whether the GitHub-hosted repository copy also contains them. See `docs/SECURITY_AUDIT.md` §1.

---

## Phase 1 — Foundation ✅ Complete (2026-08-16)

- [x] Scaffold `backend/` (Express + TypeScript + Prisma, project structure per TARGET_ARCHITECTURE.md)
- [x] Scaffold `frontend/` (Nuxt 4 + TypeScript + Tailwind + i18n)
- [x] `docker-compose.yml` + Dockerfiles (frontend, backend, postgres) — written, **not yet verified** (no Docker available in this environment, see note below)
- [x] `.env.example` with startup validation (fail fast on missing secrets — `src/config/env.ts` throws on boot if any required var is missing, no hardcoded fallback)
- [x] Linting/formatting config (ESLint, Prettier) for backend; ESLint 9 + TypeScript for frontend
- [x] Base test harness (backend: Vitest; frontend: Vitest + Vue Test Utils) — 11 backend unit tests passing (order state machine + cancellation window)

**Build verification performed (not just "it typechecks"):** backend `tsc` compiles cleanly, but the initial build was actually broken — path-alias (`@/...`) imports aren't rewritten by plain `tsc`, so `node dist/index.js` failed with `MODULE_NOT_FOUND` until `tsc-alias` was added as a post-build step. Caught by actually running the compiled server and hitting `/health`, not just by type-checking. Frontend `nuxi typecheck` and `npm run build` both pass cleanly.

## Phase 2 — Database ✅ Schema complete, migration script pending

- [x] `prisma/schema.prisma` — all 32 legacy tables modeled with UUID PKs and real FKs, per DATABASE_MIGRATION_PLAN.md. Validated with `prisma validate` and `prisma generate`.
- [x] Seed script (`prisma/seed/`) — permission catalogue + role grants (grounded in audited legacy role capabilities), subscription plans, optional demo restaurant (`SEED_DEMO_DATA=true`)
- [ ] Initial migration (`prisma migrate dev`) — **not yet run**, no local PostgreSQL server was confirmed available in this environment
- [ ] Legacy MySQL → PostgreSQL data migration script (data-cleaning steps per DATABASE_MIGRATION_PLAN.md §"Data-cleaning steps") — not started; this needs a real legacy database connection to run against, not just the schema dump

## Phase 3 — Backend (core modules complete, a few peripheral ones remain)

- [x] Auth module (JWT access/refresh for staff; signed session token helper for anonymous customer sessions)
- [x] RBAC middleware (`requirePermission`/`requireAnyPermission`, DB-backed, single mechanism replacing the legacy's three overlapping ones)
- [x] Shift-gating middleware (`requireActiveShift`, separate from RBAC per design decision, fixes the legacy midnight-rollover bug)
- [x] Tenant identity middleware (`requireStaffAuth`/`requireCustomerSession` — restaurantId comes only from the verified token, never a request param)
- [x] Menu module (categories, items, availability toggle)
- [x] Customer session module (`POST /customer/session/scan` — the actual QR-scan entry point; signed session token as the real security boundary, device fingerprint demoted to a secondary signal, fixes legacy High #9)
- [x] Orders module — create (server-authoritative pricing, fixes legacy Critical #2), cancel (60s window), status transitions (explicit state machine), item status (kitchen/waiter), payment recording (amount reconciliation, fixes legacy Critical #3), role-scoped listing (`GET /staff/orders`, `GET /staff/orders/:id`)
- [x] Liability module — single consolidated auto-creation/clear/waive/abandoned-detection service (fixes the legacy's two divergent implementations)
- [x] Restaurants module (self-service registration + settings; single consolidated onboarding service, fixes legacy's duplicated register.php/superadmin.php logic)
- [x] Staff module (CRUD, shift clock-in/out endpoints, reimplements the legacy's MySQL-trigger-only "no delete with open cash session" guard)
- [x] Tables module (CRUD, QR issuance using an opaque unguessable token instead of the legacy's structured/guessable pattern, reset, regenerate-QR)
- [x] Waiter-calls module (race-safe first-accept-wins)
- [x] Cash session module (open/close/reconcile, 1,000 RWF discrepancy threshold)
- [x] Reports module (the real per-restaurant KPI surface — dashboard stats, sales report, top items — distinct from legacy's cross-tenant Stats.php)
- [x] Subscriptions module (live plan-limit enforcement wired into order/menu-item/table/staff creation; no payment gateway, matching legacy business reality)
- [x] Admin (superadmin) module (restaurant CRUD, platform users, subscription plans, platform stats — single DB-role check, no hardcoded backdoor, fixes legacy High #7)
- [ ] Kitchen delay-escalation notifications (5/10-min tiers) — not started
- [ ] Announcements, support ticket modules — not started
- [ ] Staff-side notifications endpoint (bell icon) — not started

## Phase 4 — Frontend (started)
- [x] Project structure, Tailwind, i18n scaffolded
- [x] Staff login page — wired end-to-end to the real backend auth API (not a mock)
- [x] `useApi`/`useAuthStore` composables (in-memory access token, no localStorage — see TARGET_ARCHITECTURE)
- [x] `staff-auth` route middleware guard
- [x] Customer QR ordering flow — real implementation: QR scan → menu → cart (localStorage-persisted per table, a deliberate improvement over legacy's non-persistent cart) → place order (never sends price)
- [x] Staff dashboard — real KPI tiles + working clock-in/clock-out
- [x] Staff orders board — status transitions limited to exactly what the backend state machine accepts
- [x] Kitchen display — item-level prep queue with elapsed-time display
- [ ] Waiter interface (dedicated waiter-calls/table view — waiter can currently use the general orders board, but no dedicated waiter-calls UI yet)
- [ ] Admin/superadmin console
- [ ] Shared UI component library (only `BaseButton` exists so far; menu/cart/order components exist per-domain)

## Phase 5 — Integration
- [ ] Nuxt ↔ Express ↔ Prisma ↔ PostgreSQL end-to-end wiring
- [ ] Real-time (polling v1; SSE v1.1 if confirmed needed)

## Phase 6 — Testing
- [ ] Unit tests (services, especially order state machine, liability logic, RBAC)
- [ ] Integration tests (API + DB)
- [ ] Tenant isolation tests (explicit cross-tenant access-denial cases)
- [ ] Security tests (auth, IDOR, CSRF where applicable)

## Phase 7 — Docker
- [ ] Full `docker compose up` verification
- [ ] Production build verification (frontend + backend)

## Phase 8 — Production Readiness
- [ ] Environment configuration documented (`docs/DEPLOYMENT.md`)
- [ ] Rollback procedure documented
- [ ] Feature parity re-verified against `docs/FEATURE_PARITY_CHECKLIST.md`
- [ ] Security audit re-verified against `docs/SECURITY_AUDIT.md`

---

## Environment notes (this workspace)

- Node 24.18.1, npm 11.16.0, PostgreSQL client 16.14 available locally.
- **Docker is not installed/available in this environment** — Docker configs are written but `docker compose up` has not been verified locally; needs to be run in an environment with Docker, or by the user.
- No local PostgreSQL server was confirmed running — `prisma migrate dev` has not been run yet. Next session should start there: bring up Postgres (locally or via `docker compose up postgres`), run the migration, then `npm run prisma:seed`.
- Port 4000 is already in use by an unrelated pre-existing service on this machine (confirmed while smoke-testing the backend build) — local dev of the new backend should use a different `PORT` (e.g. 4001) via `.env`, or that port should be freed first.
