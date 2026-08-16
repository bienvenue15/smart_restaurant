# Feature Parity Checklist

Status legend: `[ ] Not audited` → `[x] Audited` → `[ ] Architecture designed` → `[ ] Backend implemented` → `[ ] Frontend implemented` → `[ ] Tested` → `[ ] Production verified`

All features below are marked **Audited** as of 2026-08-16 (full forensic read completed — see [CURRENT_SYSTEM_AUDIT.md](CURRENT_SYSTEM_AUDIT.md)). Nothing is implemented yet.

## Customer
| Feature | Status | Notes |
|---|---|---|
| QR scan → table resolution | [x] Audited | Opaque bearer QR string, no cryptographic signing in legacy — new system should sign/HMAC this |
| Device-table locking (anti-fraud) | [x] Audited | Legacy fingerprinting is weak (spoofable) — redesign with signed session token, see SECURITY_AUDIT #9 |
| Menu browsing | [x] Audited | Flat items only, no variants/modifiers exist in legacy |
| Cart | [x] Audited | Legacy: pure client memory, no persistence — recommend fixing in new frontend (localStorage) |
| Place order | [x] Audited | **Legacy has a critical pricing bug (client-trusted price) — must NOT be replicated, server-side price lookup required** |
| Add items to existing order | [x] Audited | Same pricing-bug caveat as above |
| Order status tracking | [x] Audited | Legacy SSE is dead code; order-tracking modal is broken in legacy JS — treat documented intent as spec, not the broken implementation |
| Order history (per table session) | [x] Audited | |
| Cancel order (60s window) | [x] Audited | Hardcoded threshold — make configurable |
| Call waiter | [x] Audited | |
| Request bill | [x] Audited | |
| i18n (en/fr/rw/sw) | [x] Audited | Real, used feature — preserve via Nuxt i18n |

## Restaurant Staff Portal
| Feature | Status | Notes |
|---|---|---|
| Staff login | [x] Audited | Legacy: no session regeneration on login — fix in new system |
| Shift clock-in/out | [x] Audited | Legacy has a midnight-rollover bug — fix, don't replicate |
| Role-based dashboard (admin/manager/waiter/kitchen/cashier) | [x] Audited | |
| Order management (view/confirm/update status) | [x] Audited | Kitchen/waiter transition rules documented in CURRENT_SYSTEM_AUDIT §5 |
| Order item status (kitchen prep pipeline) | [x] Audited | Derived order-status-from-items logic must become explicit function |
| Table management (CRUD, status, reset) | [x] Audited | |
| QR code generation/regeneration | [x] Audited | Legacy uses free third-party QR image APIs — replace with a vendored QR library |
| Menu management (categories/items, availability, image upload) | [x] Audited | Legacy category CRUD endpoints are actually broken (fatal error) — implement correctly from spec, not from broken code |
| Waiter call queue management | [x] Audited | First-accept-wins with row locking — preserve the race-safety, reimplement via a DB transaction/unique constraint |
| Payment recording | [x] Audited | **Critical: must reconcile amount against order total — legacy doesn't** |
| Cash session open/close/reconciliation | [x] Audited | |
| Waiter liability tracking (accountability for unpaid served orders) | [x] Audited | Real, load-bearing anti-theft feature — consolidate legacy's two divergent implementations into one service |
| Two-person approval workflow (discounts/refunds/waivers) | [x] Audited | 10,000 RWF threshold — make configurable |
| Delayed-order escalation notifications | [x] Audited | 5/10-min tiers — make configurable |
| Staff management (CRUD) | [x] Audited | Gated by per-plan seat limits |
| Reports (revenue, top items, date-ranged) | [x] Audited | Legacy has 2 duplicate/inconsistent implementations — build one clean version |
| Restaurant settings | [x] Audited | |
| Support ticket submission (staff-side) | [x] Audited | |
| Activity/audit log viewer | [x] Audited | |
| Subscription info view (read-only) | [x] Audited | No payment gateway in legacy — flagged as open business question in TARGET_ARCHITECTURE |

## Kitchen
| Feature | Status | Notes |
|---|---|---|
| Prep queue (confirmed orders only) | [x] Audited | |
| Item status updates (preparing/ready) | [x] Audited | |
| Item availability toggle ("86 an item") | [x] Audited | |
| Delay awareness | [x] Audited | Tied to escalation notifications above |

## Waiter
| Feature | Status | Notes |
|---|---|---|
| Table view | [x] Audited | |
| Order confirmation (pending→confirmed, notifies kitchen) | [x] Audited | This is the actual kitchen-notification trigger, not order creation itself |
| Waiter call handling (accept/complete) | [x] Audited | |
| Order completion (ready/served→completed) | [x] Audited | |
| Beverage item status exception | [x] Audited | Waiters can update beverage items directly, bypassing kitchen |
| Order assignment (manager-driven) | [x] Audited | Off-shift assignment guard must be preserved |
| Liability accountability | [x] Audited | See staff portal row above |

## Administration (Superadmin)
| Feature | Status | Notes |
|---|---|---|
| Restaurant CRUD (soft + hard delete) | [x] Audited | |
| Manual restaurant onboarding | [x] Audited | Legacy emails plaintext password — fix in new system |
| Subscription plan management | [x] Audited | Manual only, no gateway — see open business question |
| Platform user management (cross-restaurant) | [x] Audited | |
| Platform analytics/revenue reporting | [x] Audited | |
| Support ticket helpdesk (full, admin-side) | [x] Audited | |
| System settings (maintenance mode, business hours, etc.) | [x] Audited | |
| Database backup trigger | [x] Audited | Legacy shells out to `mysqldump` — new system needs a `pg_dump`-based equivalent via child_process with env-var auth |
| Announcements (platform broadcast) | [x] Audited | |
| Audit log / activity log (platform-wide) | [x] Audited | |

## Cross-cutting / Infrastructure
| Feature | Status | Notes |
|---|---|---|
| Multi-tenancy isolation | [x] Audited | **Legacy has real cross-tenant leakage bugs — must be fixed, not replicated** |
| RBAC / permission system | [x] Audited | Consolidate legacy's 3 overlapping mechanisms into 1 |
| Email (transactional) | [x] Audited | Welcome email, support notifications only — no order confirmation, no password reset (doesn't exist in legacy) |
| File uploads (menu images) | [x] Audited | Legacy validation is reasonably solid — preserve the approach (MIME-sniffing, size caps, resize) |
| Real-time/notifications | [x] Audited | Legacy SSE is dead code; actual mechanism is polling — see TARGET_ARCHITECTURE for recommended v1 approach |
| Payment gateway integration | [x] Audited | **None exists in legacy** — explicit open business question, do not build unless confirmed |
| SMS / WhatsApp integration | [x] Audited | **None exists in legacy** — not in scope unless requested |
| 2FA | [x] Audited | Doesn't exist in legacy — not required for parity, consider as a new-system improvement |
| Password reset | [x] Audited | Doesn't exist in legacy — recommend adding in new system as a security improvement, not blocking parity |

## Explicitly NOT carried forward (documented reasons)
| Item | Reason |
|---|---|
| SSE/EventBroadcaster subsystem (as implemented) | Dead code in legacy — the underlying *intent* (push notifications) is preserved as a documented v1.1 option in TARGET_ARCHITECTURE, not the broken implementation |
| Legacy integer auto-increment primary keys | Explicit instruction — new schema uses UUID PKs natively |
| `restaurants_backup` table | Empty structural clone, dead weight |
| Duplicate/legacy report code paths (`getMonthlyReport_old` etc.) | Confirmed dead/unreachable code |
| Hardcoded superadmin email backdoor | Security anti-pattern, not a feature |
| `staff_notifications` as a separate table from `notifications` | Consolidated into one `Notification` model — see DATABASE_MIGRATION_PLAN |
| Two independent legacy plan-definition sources (hardcoded in `register.php` vs DB-driven `subscription_plans`) | Consolidated into the single DB-driven source |
| Naive regex-based ".htaccess WAF" | Not a real security control — real WAF/reverse-proxy hardening replaces it if needed |
