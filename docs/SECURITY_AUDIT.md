# Security Audit — Legacy Smart Restaurant Application

Findings from a full forensic read of the codebase (not a black-box scan). Ordered by severity. Each item states what the new Node/Express/Prisma/PostgreSQL system must do differently — none of these are being patched in the legacy PHP code as part of this audit (per the "don't touch the reference implementation" migration rule), except where explicitly noted.

---

## CRITICAL

### 1. Hardcoded production credentials committed to source control
**File:** `src/config.php` (lines ~28-29, ~88-91), also `deploy_to_production.sh`.
Production MySQL credentials (`DB_USER=inovasiy_admin`, a real password) and the production SMTP account password (the **same** password reused for both) are hardcoded as PHP fallback defaults used whenever the corresponding environment variable isn't set.

**Action needed now, independent of migration timeline:**
- Rotate both the database password and the mail account password.
- Confirm whether the version of this repo pushed to `github.com/bienvenue15/smart_restaurant` also contains this file with the credentials in git history — if so, the credentials must be treated as fully compromised (rotation alone doesn't remove them from history; consider `git filter-repo` on that remote if it's not already public knowledge, and rotate regardless).
- This repository (local, newly `git init`-ed for this migration) has these files tracked in its first commit, but the SQL data dumps were deliberately excluded from tracking — the config file's hardcoded secrets were already present in the source tree before this session began.

**New system:** environment variables are the *only* source of secrets — no hardcoded fallback value, ever. The app should fail to start (not silently fall back to a baked-in default) if a required secret is missing. `.env.example` ships placeholders only.

### 2. Customer-submitted order prices are trusted, not server-derived
**File:** `app/controllers/api.php` (`createOrder`), `app/models/Order.php::createOrder()`.
The customer-facing `create_order` and `add_items_to_order` endpoints accept `item.price` directly from the JSON request body and sum it into the order total — validated only for numeric range (0–9,999,999), never checked against the actual `menu_items.price`. A modified request can place a real order at an arbitrary price. (Contrast: the staff-side `staff_create_order` does this correctly today, re-fetching price server-side.)

**New system:** order creation must always resolve `unitPrice` server-side from the `MenuItem` record by ID. Any client-sent price is ignored.

### 3. Payment amount is not reconciled against order total
**File:** `app/controllers/api.php::staffProcessPayment()`.
The recorded payment `amount` comes straight from the request body and is never compared to `orders.total_amount` before the order is flipped to `completed`/`paid`. A forged or buggy request could record partial payment as full payment.

**New system:** payment service computes/validates amount server-side against the authoritative order total; partial payments explicitly update `paymentStatus = PARTIAL`, never `PAID`, until the full total is reconciled.

### 4. Cross-tenant data leakage via client-supplied `restaurant_uuid`
**File:** `app/controllers/api.php` — `get_menu_data`, `get_pending_payment_orders`, `get_today_transactions`, `get_session_summary`, `get_announcements`, `dismiss_announcement`.
`getRestaurantId()` in these handlers prioritizes a **client-supplied** `restaurant_uuid` GET parameter over the session-derived tenant context, with no authorization check. Any caller can pass another restaurant's UUID and read its pending orders, today's revenue breakdown, or dismiss announcements as an arbitrary `user_id`.

**New system:** tenant ID is resolved exclusively from the authenticated session/JWT server-side (see [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md) §"Multi-tenancy enforcement") — never accepted as a request parameter for scoping purposes.

---

## HIGH

### 5. No CSRF protection anywhere
All state-changing legacy endpoints rely solely on session cookies (`SameSite=Lax`, no CSRF token). Combined with the wildcard `Access-Control-Allow-Origin: *` set globally in the `Api` constructor, this is a standard CSRF exposure (the wildcard CORS doesn't itself enable cross-origin cookie reads, but does nothing to stop same-site form/XHR-based CSRF POSTs).

**New system:** if session cookies are used for any browser-facing surface, add CSRF tokens (double-submit cookie or synchronizer token) on all mutating routes; scope CORS to an explicit allow-list, never `*`, especially once credentials are involved. If the API moves to bearer-token (JWT) auth for the staff/admin surfaces, CSRF risk is substantially reduced for those routes (tokens aren't auto-attached by the browser) — but the customer-facing table-session flow still needs an explicit decision (cookie vs. signed token).

### 6. IDOR on order lookup
**File:** `api.php::get_order`.
Accepts any syntactically-valid UUID with no ownership/session check — if an order UUID leaks (URL, screenshot, log line), anyone can view its contents, including customer name/phone on staff-created orders.

**New system:** every read-by-ID endpoint checks that the resource belongs to the requester's tenant/session context, not just that the ID is well-formed.

### 7. Superadmin authorization backdoor
**File:** `app/controllers/superadmin.php::isSuperAdmin()`.
Accepts the DB role check **or** a hardcoded fallback email `superadmin@restaurant.com` — a bootstrap escape hatch that should never exist in a production auth check.

**New system:** platform-admin authorization is a single DB/claims-based check, no hardcoded identity bypass, ever.

### 8. Several endpoints missing authorization checks entirely
- `remind_order` (api.php) — zero auth check, any request with a valid order ID writes to the activity log and returns order details.
- `staff_accept_call` — only requires *any* valid staff session, not the `manage_tables` permission every sibling waiter-call endpoint requires (a kitchen-role account could claim waiter calls).
- Three menu-category endpoints (`create_category`/`update_category`/`delete_category`) are not just insecure but **non-functional** — they call an undefined method and fatal (see [CURRENT_SYSTEM_AUDIT.md](CURRENT_SYSTEM_AUDIT.md) §6).

**New system:** every mutating route goes through the same permission-middleware pipeline — no handler is reachable without an explicit declared requirement, enforced by a lint rule or route-registration convention (e.g., a route factory that requires a permission code argument, so "forgetting" one is a type error, not a silent gap).

### 9. Weak/spoofable device fingerprinting for table-session security
**File:** `app/core/DeviceTableLock.php`.
Fingerprint = SHA-256 of IP + User-Agent + Accept-Language + Accept-Encoding — no cookie/local-storage component. Two devices behind the same NAT with an identical browser can collide; `X-Forwarded-For`/`X-Real-IP` are honored, making the IP component spoofable behind a reverse proxy if not carefully configured.

**New system:** pair the fingerprint with a signed, httpOnly session token issued on first scan (e.g., a short-lived JWT or opaque server-side session ID stored in a cookie), rather than relying on request-header-derived fingerprinting alone.

---

## MEDIUM

### 10. Rate limiting is per-process, local-disk based, and inconsistently applied
`ApiValidator::validateRateLimit()` writes counters to `sys_get_temp_dir()`, which isn't shared across web workers/load balancers, is trivially resettable, and is only opt-in on 3 of ~90 endpoints.

**New system:** centralized rate limiting (e.g. `express-rate-limit` with a shared Redis/Postgres-backed store if running multiple instances) applied by default to all public and auth-adjacent routes, not opt-in per handler.

### 11. Password/session hygiene gaps
- Staff login does not call `session_regenerate_id()` (superadmin login does) — session fixation risk on the staff portal specifically.
- No password reset flow exists anywhere in the system.
- No 2FA anywhere.
- Session cookie `secure` flag is only forced on in production via `ini_set`, not the default posture in every environment.
- Newly-generated staff/admin passwords are emailed in **plaintext** to restaurant owners on registration and superadmin-driven onboarding.

**New system:** regenerate session/rotate JWT on every privilege-level login; add a real password-reset flow (signed, time-limited token, single-use); never email a raw password — use a first-login "set your password" signed link instead; `secure`/`httpOnly`/`sameSite=strict` cookie flags on by default in all environments (relaxed only for local dev via explicit config, not silently).

### 12. No database-level integrity backstop for tenant isolation or relationships
Only 2 of 32 legacy tables have real foreign keys; `restaurant_id` scoping is enforced purely by application code discipline, and the audit found real instances (§4 above) where that discipline lapsed.

**New system:** every relationship modeled as a real Prisma/Postgres FK (see [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md)) — the database itself becomes a backstop against orphaned data and (with the middleware fix in §4) cross-tenant leakage.

### 13. Verbose logging of session/request internals
Several handlers `error_log(print_r($data, true))` including full request payloads and session key names — not directly externally exploitable, but a PII-in-logs / compliance hygiene issue.

**New system:** structured logging (e.g. `pino`) with an explicit allow-list of loggable fields; never log full request bodies or session contents by default.

### 14. Naive regex-based "WAF" in `.htaccess`, and multi-tenant query rewriting via string manipulation
`.htaccess` blocks obvious SQLi/XSS keyword patterns via regex — easily evaded, not a substitute for the parameterized queries the app does otherwise use consistently. Separately, `src/tenant_middleware.php`'s `TenantMiddleware::filterQuery()` inserts `WHERE restaurant_id = $id` into raw SQL strings via `str_ireplace()` — fragile (can corrupt queries with existing subqueries/UNIONs) and, if `$id` were ever a non-integer, a SQL injection vector (in practice `$id` is always session-derived, not user input, so exploitability is low, but the pattern itself is unsound).

**New system:** no string-manipulated SQL anywhere — Prisma's query builder / parameterized raw queries only; no regex WAF layer relied upon (a real reverse-proxy WAF or none, not a false sense of security).

---

## LOW / Hygiene

- No file-execution deny rule specifically inside the menu-image upload directory as defense-in-depth (relies entirely on MIME-sniffing at upload time, which is solid but not layered).
- No HSTS header anywhere; HTTP→HTTPS redirect is commented out in the backup `.htaccess` variant.
- No Content-Security-Policy at the web-server layer (only applied in PHP, so any path that doesn't reach PHP has none).
- Dead code inflates attack surface: unreachable legacy report methods (`getMonthlyReport_old` etc.), a disabled-but-present `staff_export_report` endpoint, the entirely dead SSE/EventBroadcaster subsystem.

**New system:** Helmet with a real CSP policy applied at the Express layer (and HSTS once HTTPS is confirmed end-to-end); no dead/unreachable route handlers shipped.

---

## What the legacy app got right (worth preserving, not just fixing)

- **Consistent use of PDO prepared statements** — no raw SQL string concatenation found anywhere in `api.php` despite its size; every dynamic query uses `?` placeholders.
- **Real MIME-sniffing on file uploads** (`finfo_file`, not trusting client `Content-Type`), with a sane size ceiling and image re-encoding.
- **Bcrypt via `password_hash`/`password_verify`** everywhere — no legacy MD5/SHA1 password paths to worry about migrating.
- **A genuine, non-trivial DB-backed RBAC design intent** (`permissions`/`role_permissions` tables with risk levels and an approval-required flag) — the new system should formalize this pattern, not invent a new one from scratch.

---

## Summary table

| # | Finding | Severity | New-system fix |
|---|---|---|---|
| 1 | Hardcoded prod DB/SMTP credentials in source | Critical | Rotate now; env-only secrets, no fallback |
| 2 | Client-trusted order pricing | Critical | Server-side price lookup, always |
| 3 | Payment amount not reconciled to order total | Critical | Server-computed/validated payment amounts |
| 4 | Cross-tenant leakage via client-supplied restaurant ID | Critical | Tenant ID from session/JWT only, never request params |
| 5 | No CSRF protection | High | CSRF tokens or token-auth for mutating routes |
| 6 | IDOR on order lookup | High | Ownership check on every by-ID read |
| 7 | Superadmin hardcoded-email backdoor | High | Single DB/claims-based check, no bypass |
| 8 | Several endpoints with missing/broken auth checks | High | Mandatory permission middleware on every route |
| 9 | Spoofable device fingerprinting | High | Pair with signed session token |
| 10 | Rate limiting weak/inconsistent | Medium | Centralized shared-store rate limiting, on by default |
| 11 | Session/password hygiene gaps | Medium | Regenerate sessions, add reset flow, no plaintext password emails |
| 12 | No DB-level FK/tenant backstop | Medium | Real FKs everywhere in Prisma schema |
| 13 | Verbose logging of sensitive data | Medium | Structured logging with field allow-list |
| 14 | Naive regex WAF / string-built tenant SQL | Medium | No string-built SQL; real WAF or none |
| — | Missing HSTS / CSP at web-server layer, dead code surface | Low | Helmet + CSP/HSTS; no dead routes shipped |
