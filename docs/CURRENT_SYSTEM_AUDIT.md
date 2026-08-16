# Current System Audit — Smart Restaurant (Legacy PHP)

**Audit date:** 2026-08-16
**Method:** Full forensic read of every controller, model, and core class in the repository (114 PHP files, ~26k lines across the five largest controllers/models alone), plus a line-by-line extraction of the MySQL schema from `db_restaurant (3).sql`. No behavior was assumed — every claim below is grounded in a specific file.

This document is the source of truth for what the *existing* system actually does. It intentionally does not describe an idealized version of the app — several real bugs and half-finished features are documented as-is, because faithfully reproducing (or deliberately dropping) them is a decision for the migration, not something to silently "fix" during the audit.

---

## 1. Architecture Overview

**No framework.** This is a hand-rolled MVC-ish app: no Composer, no `vendor/`, custom autoloading, MySQL accessed directly via PDO.

### Request flow
```
Browser
  │
  ▼
.htaccess (Apache mod_rewrite)          — cosmetic URL rewriting only
  │  e.g. /menu/<qr> → index.php?req=menu&qr=<qr>
  │       /api/events/stream → app/controllers/events.php  (bypasses everything below — SSE)
  ▼
index.php (repo root)                    — bootstraps config, session, settings enforcement
  │
  ▼
src/autoload.php → Autoload class        — reads $_GET['req'], loads app/controllers/{req}.php,
  │                                          instantiates {Req}Controller or {Req}, calls ->index()
  ▼
app/controllers/{controller}.php         — controller does its OWN internal action dispatch via
                                            $_GET['action'] switch statement (not routed by the
                                            framework — there is no per-action routing layer)
```

Controllers: `index.php` (home redirect), `menu.php` (public QR ordering), `api.php` (7,369 lines — almost the entire application's business logic, ~90 actions), `staff.php` (3,213 lines — staff portal, ~40 pages + ~35 AJAX actions), `superadmin.php` (4,460 lines — platform admin), `register.php` (self-service signup), `events.php` (standalone SSE endpoint, bypasses the router).

A **second, partially-integrated architecture** lives in `src/`: `Restaurant.php` (tenant resolution by slug/subdomain/path/session), `tenant_middleware.php` (`TenantMiddleware`/`TenantDB` — auto-injects `restaurant_id` into queries via string manipulation), `ApiValidator.php`/`ValidationHelper.php` (structured request validation), `SecurityHeaders.php`. These are real and wired into every non-register/superadmin request (`Restaurant::initialize()` runs in `Autoload::__construct`), but `TenantMiddleware`'s query-rewriting approach (regex-inserting `WHERE restaurant_id = $id` into SQL strings) is fragile and mostly unused — actual tenant scoping happens ad hoc in each model/controller method instead.

### Multi-tenancy pattern
Shared database, `restaurant_id` column per tenant-owned row (see [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md)). **Not foreign-key enforced** — isolation is 100% application-code discipline, and the audit found several endpoints that don't enforce it (see [SECURITY_AUDIT.md](SECURITY_AUDIT.md) §"Cross-tenant data leakage").

### No composer/npm build. No test suite. No CI.
Root directory is cluttered with ~20 one-off debug/migration scripts (`check_*.php`, `fix_*.php`, `test_*.php`, `mass_uuid_fix.php`) that are not wired into the app and should not be migrated — they're developer tooling artifacts from the in-flight UUID migration effort.

---

## 2. Database

Full table-by-table schema, the UUID-migration reality check, and the Prisma mapping plan live in **[DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md)**. Headline facts:

- **MySQL**, 32 real tables + 5 views + 1 stored function + 10 triggers.
- Despite three docs in the repo root (`UUID_MIGRATION_COMPLETE.md` etc.) claiming the app fully migrated from integer IDs to UUID primary keys, **the actual dump shows every table still uses `id INT AUTO_INCREMENT` as its real primary key.** UUID columns exist on 9 of 32 tables as nullable, trigger-populated side-columns — not primary keys, not universally enforced.
- **18 of 32 tables have no primary key enforced at all** in the current schema (confirmed by literal duplicate `id=0` rows in `announcement_dismissals`, `restaurant_settings`, `role_permissions`).
- Only **2 tables** (`notifications`, `waiter_liabilities`) have real foreign-key constraints. Every other relationship (including `restaurant_id` tenant scoping) is enforced only by application code.
- 10 triggers implement UUID generation, cross-table backfills, a table-occupancy side effect, audit logging, and a "can't delete staff with an open cash session" guard. None of this exists as application code — it all needs to be reimplemented in the Node service layer.

---

## 3. Authentication & Authorization

**100% cookie-based PHP sessions. No JWT, no OAuth, no API keys in active use.**

### Two separate login systems
1. **Staff login** (`app/controllers/staff.php`) — `staff_users` row lookup by username, `password_verify()` (bcrypt). Sets `$_SESSION['staff_user']`, `staff_uuid`, `staff_role`, etc. **Does not call `session_regenerate_id()`** on login (session fixation risk).
2. **Superadmin login** (`app/controllers/superadmin.php`) — separate query (`role = 'super_admin'`), sets `$_SESSION['user_id']/['email']/['role']`, **does** call `session_regenerate_id(true)`. Authorization additionally accepts a **hardcoded fallback email** `superadmin@restaurant.com` alongside the DB role check — a legacy backdoor that should be removed, not carried into the rewrite.

### Customer "auth"
No login at all — identity is the table session established when a QR code is scanned (`$_SESSION['table_id']`, `restaurant_uuid`, `device_fingerprint`). Every customer-facing write endpoint cross-checks the request's `table_id` against the session's.

### RBAC — three overlapping mechanisms (a real migration hazard)
1. **DB-backed permission codes (the intended mechanism):** `staff_users.role → role_permissions(role, permission_code) → permissions(code, category, risk_level, requires_approval)`. Exposed via `Permission::require($code)` / `requireAny()` / `check()` (`app/core/Permission.php`). ~40 permission codes across 7 categories.
2. **Hardcoded inline role arrays:** dozens of call sites bypass the permission system entirely with `in_array($role, ['admin','manager'])`-style checks, both in controllers and in views (`_sidebar.php`). These can and do drift out of sync with the DB-driven table.
3. **Shift-gating:** `Permission::requireShift()` / `requireActiveShift()` blocks nearly every mutating staff action unless the staff member is currently clocked in (`staff_shifts`), enforced centrally in the `Api` constructor and per-page in `staff.php`. This is a real, load-bearing business rule.

**Roles found in the database:** `super_admin` (platform operator, `restaurant_id = NULL`), `admin` (restaurant owner), `manager`, `waiter`, `kitchen`, `cashier`. No separate "owner" role literal — `admin` is described as "owner" in UI copy only.

**Recommendation for the rewrite:** collapse all three mechanisms into one permission-code-based RBAC system (roles + permissions + a join table, enforced by Express middleware), model shift-gating as a separate middleware, and eliminate hardcoded role-array checks in views.

---

## 4. Feature Inventory

### 4.1 Customer (public, QR-driven, no account)
| Feature | Implementation | Notes |
|---|---|---|
| QR scan → menu | `menu.php` controller, `qr` param resolves a `restaurant_tables` row | QR string is an opaque bearer value, not signed/encoded — see [SECURITY_AUDIT.md](SECURITY_AUDIT.md) |
| Device-table lock | `DeviceTableLock` (fingerprint = SHA-256 of IP+UA+headers), 120-min lock, prevents 2 devices on 1 table | Fingerprint is spoofable behind shared IP/UA (e.g. same NAT) |
| Menu browsing | `Menu::getAllCategoriesWithItems()` | Flat items, no variants/modifiers exist anywhere in the schema |
| Cart | Pure client-side JS array (`app.js`), **not persisted** | Page refresh loses the cart; only server-side order *history* survives |
| Place order | `create_order` API action | **Price is trusted from the client** with only range validation — no server-side lookup against `menu_items.price`. Critical gap, see §6 |
| Order status | SSE client code exists (`realtime-events.js`) but the server-side broadcaster is **never instantiated** — dead plumbing. Actual updates rely on the client re-polling `get_order_status` | The polling fallback in `customer-menu.js` is explicitly stubbed out as "deprecated" — if SSE silently fails, the UI does not refresh until manual reload |
| Call waiter / request bill | `call_waiter` / `request_bill` → `waiter_calls` table | State machine: `pending → acknowledged → completed` (or `cancelled`) |
| Cancel order | 60-second window, `pending` status only | Hard-coded threshold |
| Order tracking modal | `CustomerMenu.showOrderTracking()` | **Broken** — references an undefined JS variable, throws at runtime |

### 4.2 Restaurant Staff Portal (`staff.php`)
| Role | Capabilities |
|---|---|
| `admin` (owner) | Full restaurant management: menu, tables, staff, settings, reports, subscription (read-only), approvals |
| `manager` | Same operational surface as admin minus a few admin-only pages (`staff_manage`, `subscription`); exact grants are DB-driven via `role_permissions` |
| `waiter` | Own orders only, table reset, waiter-call handling, beverage item status updates, liability accountability |
| `kitchen` | Prep-queue orders only, item-level status updates (`preparing`/`ready`), availability toggling ("86'ing" items) |
| `cashier` | Cash session open/close, payment recording, liability visibility |

Cross-cutting features: **staff shift clock-in/out** (gates nearly all mutating actions), **waiter liability tracking** (auto-creates a financial-accountability record whenever an order is served/completed while unpaid — a genuine theft/walkout-prevention mechanism, see §5), **two-person approval workflow** for high-risk actions (discounts, refunds, waivers above a threshold), **cash session reconciliation** (opening/closing balance, variance flagging), **delayed-order escalation** (notifies waiter then manager as prep time overruns), **support ticket system** (staff-side).

### 4.3 Kitchen Workflow
Kitchen never touches order-level status directly — only item-level (`preparing`/`ready`). The order's aggregate status is *derived* from the mix of its items' statuses via `updateOrderStatusFromItems()` (all-served→ready, any-preparing→preparing, etc.) — an implicit fan-in state machine that must become an explicit, tested function in the rewrite. Kitchen only sees orders once a waiter has confirmed them; raw `pending` orders are invisible to kitchen.

### 4.4 Waiter Workflow
Confirms `pending→confirmed` orders (this is what actually notifies kitchen — not order creation itself). Marks orders `completed` once ready/served. Handles waiter calls (first-to-accept-wins, row-locked to prevent double-claim). Can only edit customer-placed orders they didn't create if explicitly assigned; freely edits orders they rang in themselves. Accrues liability for unpaid served orders.

### 4.5 Superadmin (Platform Operator, `superadmin.php`)
Restaurant CRUD (soft delete via `is_active=0`, or hard cascade delete), manual restaurant onboarding (creates restaurant + default admin user + seeds 4 menu categories + emails plaintext credentials), subscription plan management (**no payment gateway — entirely manual**, `extend_subscription` just pushes a date forward), platform user management across all restaurants, platform analytics/revenue reporting, a full support-ticket helpdesk system, system settings (including maintenance mode), a real `mysqldump`-based backup trigger, and an announcement broadcast system.

### 4.6 Reporting
Two distinct surfaces, easy to conflate:
- **`Stats.php`** — tiny, computes **global, cross-tenant, unscoped** platform metrics for the public marketing homepage (total orders across ALL restaurants, etc.). Not a per-restaurant report.
- **The real per-restaurant reporting** lives in `api.php` (`staff_get_report`/`get_daily_report`/etc. — with a redundant duplicate implementation, see §6) and `Staff::getDashboardStats()` / `WaiterLiability::getLiabilityStats()`.

---

## 5. Business Rules That Must Be Preserved

These are not obvious from a UI screenshot and would be easy to silently drop in a rewrite:

1. **Waiter liability** — whenever an order transitions to `served`/`completed` while `payment_status !== 'paid'`, a liability record is auto-created against the responsible staff member (resolved via a priority chain: server → confirmer → creator). Cleared automatically on payment; can be marked `loss` (walkout) or `waived` (manager forgiveness, requiring second-approval above 10,000 RWF). This is a genuine anti-theft accountability system, not decorative.
2. **Anti-walkout table visibility** — served/completed orders that aren't paid keep the table "occupied" and visible, explicitly "to prevent theft" (a comment in the source code, preserved verbatim as design intent).
3. **Shift-gating** — every mutating staff action (not just financial ones) requires an active clock-in, enforced centrally.
4. **60-second order cancellation window**, **120-minute device-table lock**, **120-minute abandoned-order auto-loss detection**, **10,000 RWF liability-waiver approval threshold**, **1,000 RWF cash-session discrepancy threshold**, **5/10-minute order-delay escalation tiers** — all hardcoded numeric business rules that should become configurable settings in the rewrite.
5. **Off-shift assignment guard** — a waiter cannot be assigned an order/call unless currently clocked in.
6. **Subscription/plan limits enforced live** — table count, user count, menu item count, and monthly order count are checked against the restaurant's plan on every relevant create action, not just at signup.
7. **Waiter beverage exception** — waiters (not just kitchen) may update item status for beverage-category items, since drinks skip the kitchen prep pipeline.

---

## 6. Known Bugs / Broken or Dead Features (do not silently replicate — treat as bugs to fix, not spec)

| Issue | Location | Impact |
|---|---|---|
| `create_category`/`update_category`/`delete_category` call `$this->isLoggedIn()`, a method that doesn't exist on the `Api` class | `api.php` | Fatal PHP error, endpoints are non-functional |
| Customer order-tracking modal references an undefined JS variable | `customer-menu.js` | Throws at runtime, feature is broken |
| SSE event broadcaster (`EventBroadcaster`) is fully built but **never instantiated anywhere** in the codebase | `api.php`, `EventBroadcaster.php` | The entire SSE push-notification system is dead code; real "real-time" updates are client polling of REST endpoints |
| `events.php` references undefined constant `DB_PASS` (actual constant is `DB_PWD`) | `app/controllers/events.php` | The SSE endpoint likely fatals on PHP 8+ unless a stray fallback saves it |
| Two independent, duplicated liability auto-creation implementations (`Order.php` vs `Staff.php`) with **different** "who's responsible" priority logic | `app/models/Order.php`, `app/models/Staff.php` | Can assign liability to different staff depending on which code path fires for the same event |
| Two independent, duplicated report-generation code paths with inconsistent column assumptions (`id` vs `uuid`) | `api.php` | `staff_get_calls` joins non-existent `staff`/`table_id` columns — likely 500s |
| `payment_status = 'pending'` comparisons in `Order.php` — `'pending'` is not a valid value of that enum (real values: `unpaid/partial/paid/refunded`) | `app/models/Order.php` | These WHERE clauses silently match nothing — likely dead conditions |
| `staff_shifts` clock-out query filters on `DATE(clock_in) = CURDATE()` | `Staff.php` | Overnight shifts clocked out after midnight are never found — shift gets stuck "active" forever |
| `waiter_liabilities.deduction_status`/`deducted_amount` (payroll deduction) and `staff_shifts.status='break'` exist in the schema but no code reads/writes them | schema + models | Half-built features — decide to build out or drop |
| `restaurant_settings.geofence_radius` / `require_wifi_verification` exist in seed data but aren't wired into any enforcement code | schema | Half-built geofenced clock-in feature |
| `menu_categories`, `waiter_calls`, `cash_sessions`, `cash_transactions` have **no UUID columns** despite migration docs claiming otherwise | schema | Documentation is aspirational, not accurate — verify everything against the dump, not the docs |

---

## 7. Integrations

| Integration | Status |
|---|---|
| Payment gateway (Stripe/PayPal/MTN MoMo API/Flutterwave/etc.) | **None.** "Mobile Money" is a manually-recorded payment-method label — the cashier receives physical cash or confirms a manual USSD transfer and types in a reference; no API call to any gateway. |
| SMS | **None found anywhere.** |
| WhatsApp | **None found anywhere.** |
| Email | Real: PHPMailer 6.10 (vendored, not Composer-managed) via SMTP (`mail.inovasiyo.rw`). Sends: restaurant-onboarding welcome email (**containing a plaintext generated password**), support-ticket reply/status-change notifications. **No** password-reset email (feature doesn't exist), **no** order-confirmation email, **no** subscription-expiry warning email (the method exists but is a stub with a literal `// TODO`). |
| QR generation | Calls two public third-party QR-image APIs (`api.qrserver.com`, `chart.googleapis.com`) with a local GD-drawn placeholder fallback — no vendored QR library. |
| File uploads | Menu item images only (no logo upload, no avatar upload). Real MIME-sniffing via `finfo`, GD-based resize/recompress to under 100KB, or a stricter fallback path without GD. Reasonably solid. |
| 2FA / password reset | **Neither exists anywhere in the codebase.** |

---

## 8. Frontend Assets (for migration reference)

Vanilla JS, no framework/bundler: `app.js` (880 lines, customer cart/ordering), `customer-menu.js` (592 lines, SSE consumption + UI), `staff-dashboard.js` (1,101 lines), `realtime-events.js` (256 lines, SSE client), plus language-switcher scripts (the app has i18n support — `en/fr/rw/sw` — via `src/Language.php` and `src/languages/*.php`, worth preserving in the Nuxt rewrite via `@nuxtjs/i18n`).

---

## 9. Critical Finding — Requires Immediate Action, Independent of the Migration Timeline

**`src/config.php` hardcodes real production credentials in source control:**
- Production database user/password (`inovasiy_admin` / a real-looking password) as a fallback default when the `DB_PWD` env var isn't set.
- The identical password reused for the production SMTP account (`MAIL_SMTP_PASSWORD`).
- The same password also appears in `deploy_to_production.sh`.

This file is committed to the repository (and was already present before this audit began — it predates any action taken here). See **[SECURITY_AUDIT.md](SECURITY_AUDIT.md) §1** for the full writeup and recommended remediation. **Recommend rotating both credentials and confirming whether the GitHub-hosted copy of this repository also contains them**, independent of and before completing the rest of this migration.
