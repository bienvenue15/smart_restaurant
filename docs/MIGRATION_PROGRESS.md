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

## Phase 2 — Database ✅ Schema + initial migration complete and verified against a real database

- [x] `prisma/schema.prisma` — all 31 legacy tables modeled with UUID PKs and real FKs, per DATABASE_MIGRATION_PLAN.md. Validated with `prisma validate` and `prisma generate`.
- [x] Seed script (`prisma/seed/`) — permission catalogue + role grants (grounded in audited legacy role capabilities), subscription plans, optional demo restaurant (`SEED_DEMO_DATA=true`)
- [x] Fixed a real schema bug: only FK columns had `@map(snake_case)` — ~160 other scalar columns (`isActive`, `createdAt`, etc.) were left as unmapped camelCase, inconsistent with the rest of the schema. Fixed programmatically (diffed before applying), required a dev-database reset (explicit user consent obtained — Prisma itself blocks an agent from running `migrate reset` without it).
- [x] Initial migration (`prisma migrate dev`) — **run and applied** against a real local PostgreSQL 16 server (`smartresto` database). All 31 tables + FKs + indexes confirmed via `\dt`/`\d` in psql.
- [x] Seed data verified in the real database: 26 permissions, 92 role-permission grants, 4 subscription plans.
- [x] **Full order lifecycle verified end-to-end against the real database via the actual HTTP API** (not mocked): registered a restaurant, logged in, created a table and menu item, scanned the QR to get a signed customer session, placed an order with a **forged price of 1 RWF** (server correctly computed the real total of 7,000 RWF from the menu item's actual price — Critical #2 fix confirmed with real data, not just unit-tested), confirmed the shift-gating middleware correctly blocked order confirmation until clocking in, clocked in, confirmed the order, recorded payment, confirmed the table was released back to `AVAILABLE`, confirmed dashboard stats correctly reflected the completed order.
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
- [x] Liability management routes (list/waive/mark-loss/stats — the auto-create/clear engine existed, staff had no way to act on one)
- [x] Notifications module (list/mark-read/mark-all-read), wired into the real trigger points: kitchen notified on order CONFIRMED (not creation — matches legacy's explicit intent), managers notified on waiter calls and menu availability toggles
- [x] Activity log module (merges `StaffActivityLog` + `AuditTrail`)
- [x] Adjustments module — two-person approval workflow for discounts/refunds (auto-approved within a staff member's own limit/permission, otherwise queued for manager approval)
- [x] Seeded platform superadmin bootstrap account (env-configurable credentials, always seeded — without it there's no way to reach the admin console on a fresh deploy)
- [ ] Kitchen delay-escalation notifications (5/10-min tiers) — not started
- [ ] Announcements, support ticket modules — not started
- [ ] File uploads (menu images), email sending — not started

## Phase 4 — Frontend (core surfaces complete)
- [x] Project structure, Tailwind, i18n scaffolded
- [x] Staff login page — wired end-to-end to the real backend auth API, redirects by role (staff vs superadmin)
- [x] `useApi`/`useAuthStore` composables (in-memory access token, no localStorage — see TARGET_ARCHITECTURE)
- [x] `staff-auth`/`admin-auth` route middleware guards
- [x] Customer QR ordering flow — real implementation: QR scan → menu → cart (localStorage-persisted per table, a deliberate improvement over legacy's non-persistent cart) → place order (never sends price)
- [x] Staff dashboard — real KPI tiles + working clock-in/clock-out
- [x] Staff orders board — status transitions limited to exactly what the backend state machine accepts
- [x] Kitchen display — item-level prep queue with elapsed-time display
- [x] Staff layout with sidebar navigation across all staff pages
- [x] Tables management (create/reset/delete, copy customer menu link)
- [x] Menu management (categories/items CRUD, availability toggle)
- [x] Team management (staff CRUD with role assignment)
- [x] Waiter-calls page (accept/complete queue)
- [x] Cash session page (open/close, running balance, transaction list)
- [x] Superadmin console — dashboard, restaurant onboarding (temp password shown once, never emailed), platform users list, subscription plans
- [ ] Liability management, activity log, notifications bell, approval-queue — backend ready, no frontend page yet
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

- Node 24.18.1, npm 11.16.0, a local PostgreSQL 16 server (Windows service `postgresql-x64-16`) all available.
- **Docker is not installed/available in this environment** — Docker configs are written but `docker compose up` has not been verified locally; needs to be run in an environment with Docker, or by the user.
- Local PostgreSQL is set up and working: database `smartresto`, migrated and seeded (see Phase 2 above). Connection string lives in `backend/.env` (gitignored, not in this doc for the same reason).
- Port 4000 was intermittently occupied by an unrelated service on this machine during earlier smoke tests — as of the last verification pass it was free and the backend ran on it without conflict, but if it recurs, use a different `PORT` via `.env`.
