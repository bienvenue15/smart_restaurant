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

## Phase 1 — Foundation (Next)

- [ ] Scaffold `backend/` (Express + TypeScript + Prisma, project structure per TARGET_ARCHITECTURE.md)
- [ ] Scaffold `frontend/` (Nuxt 4 + TypeScript + Tailwind)
- [ ] `docker-compose.yml` + Dockerfiles (frontend, backend, postgres)
- [ ] `.env.example` with startup validation (fail fast on missing secrets)
- [ ] Linting/formatting (ESLint, Prettier) for both apps
- [ ] Base test harness (backend: Jest/Vitest + Supertest; frontend: Vitest + Vue Test Utils)

## Phase 2 — Database
- [ ] `prisma/schema.prisma` per DATABASE_MIGRATION_PLAN.md
- [ ] Initial migration
- [ ] Seed script (roles, permissions catalogue, subscription plans, demo restaurant)
- [ ] Legacy MySQL → PostgreSQL data migration script (data-cleaning steps per DATABASE_MIGRATION_PLAN.md §"Data-cleaning steps")

## Phase 3 — Backend
- [ ] Auth module (JWT access/refresh, customer session tokens)
- [ ] RBAC middleware (permission codes + shift-gating, consolidated)
- [ ] Tenant middleware (session/JWT-derived only)
- [ ] Restaurants, staff, menu, tables modules
- [ ] Orders module (server-authoritative pricing — fixes legacy Critical #2)
- [ ] Kitchen, waiter modules
- [ ] Liability module (single consolidated implementation)
- [ ] Payments module (amount reconciliation — fixes legacy Critical #3)
- [ ] Cash session module
- [ ] Reports module
- [ ] Subscriptions module (limits/enforcement)
- [ ] Announcements, support ticket modules
- [ ] Admin (superadmin) module

## Phase 4 — Frontend
- [ ] Customer QR ordering flow
- [ ] Staff portal (role-aware dashboard)
- [ ] Kitchen display
- [ ] Waiter interface
- [ ] Admin/superadmin console
- [ ] Shared UI component library

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
- **Docker is not installed/available in this environment** — Docker configs will be written and reviewed, but `docker compose up` cannot be verified locally here; needs to be run in an environment with Docker, or by the user.
- No local PostgreSQL server was confirmed running at the time of this log — needs verification before Phase 2 execution.
