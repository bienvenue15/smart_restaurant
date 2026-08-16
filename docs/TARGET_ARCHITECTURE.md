# Target Architecture — Smart Restaurant Platform

## Stack

| Layer | Technology | Notes |
|---|---|---|
| Frontend | Nuxt 4 / Vue 3 / Vite / Tailwind CSS / TypeScript | SSR for the public marketing site + customer QR menu (speed, SEO); SPA-like behavior for authenticated dashboards is fine |
| Backend | Node.js 22+ / Express / TypeScript | Modular monolith, not microservices |
| Validation | **Joi** | Applied at the route layer, before any service/Prisma call |
| ORM | Prisma | PostgreSQL, UUID primary keys throughout (see [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md)) |
| Database | PostgreSQL | Real foreign keys everywhere — a deliberate fix over the legacy schema, which had FK constraints on only 2 of 32 tables |
| Auth | JWT (access + refresh) for staff/admin/superadmin; signed session token for anonymous customer table-sessions | See §"Auth & Session Model" |
| Infra | Docker Compose: `frontend`, `backend`, `postgres` | No Kubernetes, no Redis/Kafka/GraphQL/Elasticsearch unless a concrete need emerges post-launch |

No microservices, no event bus, no premature infrastructure. The legacy app's own failure mode wasn't "too monolithic" — it was inconsistent business-rule enforcement inside one big app. The fix for that is discipline (services, middleware, real FKs), not more moving parts.

---

## High-level diagram

```
                              INTERNET
                                 │
                                 ▼
                          Reverse Proxy (TLS)
                                 │
                 ┌───────────────┴───────────────┐
                 │                                │
                 ▼                                ▼
          Nuxt Frontend                      Express API
      (marketing, customer QR menu,           (REST, /api/v1/*)
       staff/admin dashboards — SSR/SPA)            │
                 │                                  ▼
                 │                             Prisma Client
                 │                                  │
                 │                                  ▼
                 │                             PostgreSQL
                 │                          (UUID PKs, real FKs)
                 ▼
     Customer / Restaurant / Kitchen /
     Waiter / Cashier / Admin / Superadmin
     interfaces (role-gated by JWT claims)
```

---

## Backend module layout (modular monolith, domain-oriented)

```
backend/
├── prisma/
│   ├── schema.prisma
│   ├── migrations/
│   └── seed/                      # roles, permissions, subscription plans, demo restaurant
├── src/
│   ├── config/                    # env loading + startup validation (fail fast, no hardcoded fallbacks)
│   ├── middleware/
│   │   ├── auth.ts                 # JWT verification → req.user
│   │   ├── permission.ts           # requirePermission(code) — replaces Permission::require()
│   │   ├── tenant.ts                # resolves restaurantId from req.user ONLY, never from params/body
│   │   ├── shift.ts                 # requireActiveShift() — separate from RBAC, mirrors legacy intent correctly this time
│   │   └── errorHandler.ts
│   ├── modules/
│   │   ├── auth/                   # login, refresh, logout
│   │   ├── restaurants/            # tenant CRUD, settings, onboarding
│   │   ├── staff/                  # staff CRUD, shifts, roles
│   │   ├── menu/                   # categories, items, availability
│   │   ├── tables/                 # table CRUD, QR issuance
│   │   ├── orders/                 # order lifecycle, server-side pricing (fixes legacy Critical #2)
│   │   ├── kitchen/                # item-status transitions, availability toggles
│   │   ├── waiter/                 # waiter calls, assignment
│   │   ├── liability/              # waiter accountability — ports the real legacy business rule, once (not duplicated)
│   │   ├── payments/                # payment recording, reconciliation (fixes legacy Critical #3)
│   │   ├── cash/                    # cash session open/close/reconcile
│   │   ├── reports/                  # per-restaurant KPIs (the real reporting surface, not the legacy Stats.php public-metrics stub)
│   │   ├── subscriptions/            # plan limits/enforcement, superadmin-managed (still no payment gateway unless the business wants one added — see open question)
│   │   ├── announcements/
│   │   ├── support/                  # ticket system
│   │   └── admin/                    # superadmin-only platform operations
│   ├── validators/                  # Joi schemas, one per module
│   ├── services/                    # shared cross-module logic (e.g. notification dispatch)
│   ├── utils/
│   └── index.ts
└── tests/
```

Each module owns its routes, controller, service, and Joi validators — mirrors the legacy domain boundaries (menu/orders/staff/tables/etc. were already conceptually separate, just tangled together inside `api.php`).

---

## Auth & session model

The legacy app is 100% cookie-session, zero tokens. Two different identity flows need two different treatments in the new system:

- **Staff / admin / superadmin**: JWT access token (short-lived, ~15 min) + refresh token (httpOnly cookie, longer-lived, rotated on use). Role and `restaurantId` are JWT claims, verified server-side on every request — this directly fixes legacy Critical #4 (client-supplied `restaurant_uuid` trusted over session) because there is no request parameter path to tenant scoping anymore, only the verified token claim.
- **Customer (anonymous, QR-driven)**: a signed, short-lived session token issued when a QR is scanned and the device-table lock succeeds, stored as an httpOnly cookie. Replaces the legacy raw-fingerprint-only trust model (legacy High #9) — the fingerprint becomes a secondary anti-abuse signal, not the sole authority.
- **No hardcoded bypass identities anywhere** (fixes legacy High #7 — the superadmin backdoor email).

---

## RBAC — single mechanism, not three

Legacy had three overlapping, drifting permission mechanisms (DB-driven codes, hardcoded role arrays, and shift-gating conflated with permissions — see [CURRENT_SYSTEM_AUDIT.md](CURRENT_SYSTEM_AUDIT.md) §3). New system:

1. `Role` enum + `Permission` table + `RolePermission` join table (ported faithfully from the legacy `role_permissions`/`permissions` design — that part of the legacy architecture was sound, just inconsistently applied).
2. A single `requirePermission(code)` Express middleware is the **only** way a route checks authorization — no inline `if (role === 'admin')` checks scattered through controllers.
3. Shift-gating (`requireActiveShift`) is a separate, explicit middleware composed alongside `requirePermission` where the legacy business rule calls for it — never silently bundled into the permission check itself.

---

## Order lifecycle — explicit state machine, not implicit SQL aggregation

Legacy derives order-level status from item-level status via ad hoc logic scattered in `updateOrderStatusFromItems()`. New system: a single, unit-tested `deriveOrderStatus(items: OrderItemStatus[]): OrderStatus` pure function in `modules/orders/`, with the full transition table made explicit (see [CURRENT_SYSTEM_AUDIT.md](CURRENT_SYSTEM_AUDIT.md) §5 for the legacy transition rules to preserve, and §6 for the bugs — e.g. the `payment_status = 'pending'` dead-comparison bug — to fix rather than replicate).

Waiter liability auto-creation, which legacy implements **twice** with different priority logic (Order.php vs Staff.php), becomes a single `LiabilityService.evaluateOnStatusChange()` call, invoked from one place.

---

## Real-time strategy

Legacy built a complete SSE client+server pipeline that is **entirely dead code** — the broadcaster is never instantiated, so nothing ever gets pushed; actual "live" updates today are client-side polling of REST endpoints ([CURRENT_SYSTEM_AUDIT.md](CURRENT_SYSTEM_AUDIT.md) §6).

Recommendation: **start with the same polling model that's actually running in production today** (short-interval polling of an orders/notifications endpoint from the Nuxt client) rather than assuming SSE/WebSocket parity is required — nothing in production currently depends on push semantics. If/when the product wants genuine push (kitchen displays updating instantly, for example), add a lightweight SSE endpoint backed by Postgres `LISTEN/NOTIFY` (no separate message broker needed at this scale) as a v1.1 improvement — this finally realizes what the legacy code clearly intended to build but never wired up, without introducing Kafka/Redis prematurely.

---

## Multi-tenancy

Shared database, `restaurantId` UUID FK on every tenant-owned table, enforced by:
1. Real Postgres foreign keys (legacy had none for this — see [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md)).
2. `tenant.ts` middleware that resolves `restaurantId` exclusively from the verified JWT, injected into every service call — services accept `restaurantId` as a required parameter, never look it up from `req.query`/`req.body`.
3. Optional: Postgres Row-Level Security as defense-in-depth once the schema stabilizes.

---

## Frontend structure (Nuxt 4)

```
frontend/
├── app/
├── assets/
├── components/
│   ├── ui/                # Button, Modal, Drawer, Table, DataTable, Form controls, Toast, Badge, Card, Pagination, EmptyState, ErrorState
│   ├── customer/           # menu browsing, cart, order tracking, waiter-call
│   ├── staff/               # role-aware dashboard widgets, order board, cash session UI
│   ├── kitchen/              # KDS-style prep queue
│   └── admin/                 # superadmin console
├── composables/
│   ├── useAuth.ts
│   ├── useOrders.ts
│   └── useTenant.ts
├── layouts/                # customer, staff, kitchen, admin, marketing
├── middleware/              # route guards mirroring backend permission codes
├── pages/
├── plugins/
├── server/                  # Nuxt server routes only where SSR needs a same-origin proxy
├── types/
├── nuxt.config.ts
└── package.json
```

i18n preserved via `@nuxtjs/i18n` (legacy supports `en/fr/rw/sw` — a real, used feature, not incidental).

---

## Infrastructure

```
docker/
├── frontend.Dockerfile     # multi-stage: build → slim runtime
├── backend.Dockerfile      # multi-stage: build → slim runtime, non-root user
docker-compose.yml           # frontend, backend, postgres, with healthchecks + named volume for postgres data
.env.example                 # placeholders only, validated at startup — no hardcoded fallback values anywhere (fixes legacy Critical #1)
```

---

## Open questions for the business (not engineering decisions — flagging rather than guessing)

1. **Payment gateway**: legacy has zero payment gateway integration — "mobile money" is a manual cash-equivalent entry by staff. Does the new platform need real MTN MoMo / card gateway integration, or should it keep the manual-recording model? (Master prompt says don't add integrations the source system doesn't have — flagging for explicit confirmation before scoping.)
2. **Legacy UUIDs already in circulation**: some restaurants may have physical QR codes printed encoding the current `qr_code` string, which is independent of the (unenforced) legacy `uuid` columns — table QR codes can carry over as-is since `qrCode` isn't tied to the primary-key scheme change. No action needed here, just confirming no re-printing is required.
3. **SSE/real-time**: confirmed above as a v1.1 nice-to-have, not a launch blocker, since nothing in production currently relies on push updates. Flag if the business considers this a hard requirement for launch.
