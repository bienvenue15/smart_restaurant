# Feature Parity Checklist

Status legend: `Not audited` → `Audited` → `Architecture designed` → `Backend implemented` → `Frontend implemented` → `Tested` → `Production verified`

Updated 2026-08-16 (mid-Phase 3/4). Every feature was audited in Phase 0; this pass reflects actual implementation progress since then, not just intent — several rows below have a backend but no frontend yet, or vice versa, and that's called out explicitly rather than rounded up.

## Customer
| Feature | Status | Notes |
|---|---|---|
| QR scan → table resolution | Frontend implemented | Signed session token replaces legacy's fingerprint-only trust (SECURITY_AUDIT #9) |
| Device-table locking (anti-fraud) | Backend implemented | No dedicated frontend surface needed — enforced transparently during scan |
| Menu browsing | Frontend implemented | |
| Cart | Frontend implemented | localStorage-persisted per table (legacy had no persistence — documented improvement, not a parity requirement) |
| Place order | Frontend implemented | Client never sends price; server always looks it up (fixes legacy Critical #2) |
| Add items to an existing order | **Not built** | Legacy `add_items_to_order` has no new-system equivalent yet |
| Order status tracking (post-placement) | **Not built** | No customer-facing GET-order-status endpoint yet; polling approach is decided (TARGET_ARCHITECTURE) but not implemented |
| Order history (per table session) | **Not built** | |
| Cancel order (60s window) | Backend implemented | No cancel button in the UI yet |
| Call waiter | Backend implemented | No call-waiter UI in the customer menu page yet |
| Request bill | Backend implemented | Via the generic waiter-call endpoint (`requestType: BILL`); no dedicated button yet |
| i18n (en/fr/rw/sw) | Architecture designed | Locales configured in `nuxt.config.ts`; no translation strings written yet |

## Restaurant Staff Portal
| Feature | Status | Notes |
|---|---|---|
| Staff login | Frontend implemented | |
| Shift clock-in/out | Frontend implemented | Midnight-rollover bug fixed (queries by open/closed status, not `DATE(clockIn)`) |
| Role-based dashboard | Frontend implemented | KPI tiles + shift toggle |
| Order management (view/confirm/complete) | Frontend implemented | Status buttons limited to exactly what the backend state machine accepts |
| Order item status (kitchen prep pipeline) | Frontend implemented | |
| Table management (CRUD, status, reset) | Backend implemented | No staff-facing tables UI page yet |
| QR code generation/regeneration | Backend implemented | Opaque unguessable token (not the legacy's guessable pattern); no UI yet |
| Menu management (categories/items, availability) | Backend implemented | No staff-facing menu-editor UI yet; image upload not built |
| Waiter call queue management | Backend implemented | Race-safe accept; no staff UI page yet |
| Payment recording | Backend implemented | Amount reconciled against order total (fixes legacy Critical #3); no UI yet |
| Cash session open/close/reconciliation | Backend implemented | 1,000 RWF discrepancy threshold; no UI yet |
| Waiter liability tracking | Backend implemented (auto-creation/clearing only) | The auto-create-on-serve/clear-on-payment engine is consolidated into one service and wired into order/payment flows; **staff-facing list/waive/mark-loss endpoints are not yet exposed as routes** |
| Two-person approval workflow (discounts/refunds/waivers) | Architecture designed | `OrderAdjustment` model exists in schema; no service/routes yet |
| Delayed-order escalation notifications | Architecture designed | Not started |
| Staff management (CRUD) | Backend implemented | Seat-limit enforced; no UI yet |
| Reports (revenue, top items, dashboard) | Backend implemented, dashboard tiles Frontend implemented | Sales/top-items reports have working endpoints but no dedicated frontend page yet |
| Restaurant settings | Backend implemented | No UI yet |
| Support ticket submission (staff-side) | **Not built** | |
| Activity/audit log viewer | **Not built** | No GET endpoint over `StaffActivityLog`/`AuditTrail` yet |
| Subscription info view | Backend implemented | Available via `GET /staff/restaurants/me`; no dedicated UI page |

## Kitchen
| Feature | Status | Notes |
|---|---|---|
| Prep queue (confirmed+ orders only) | Frontend implemented | |
| Item status updates (preparing/ready) | Frontend implemented | |
| Item availability toggle ("86 an item") | Backend implemented | Endpoint exists; no toggle control in the kitchen UI yet |
| Delay awareness | **Not built** | Tied to the not-yet-built escalation feature above |

## Waiter
| Feature | Status | Notes |
|---|---|---|
| Table view | Backend implemented | Table numbers show in the orders board; no dedicated table-status view |
| Order confirmation (pending→confirmed) | Frontend implemented | |
| Waiter call handling (accept/complete) | Backend implemented | No dedicated waiter UI yet — usable only via API today |
| Order completion | Frontend implemented | |
| Beverage item status exception | **Not built** | Legacy lets waiters update beverage-category items directly, bypassing kitchen — not yet ported |
| Order assignment (manager-driven, off-shift guard) | **Not built** | |
| Liability accountability | Backend implemented (auto only) | Same caveat as the staff-portal row above |

## Administration (Superadmin)
| Feature | Status | Notes |
|---|---|---|
| Restaurant CRUD | Backend implemented (soft delete/deactivate only) | Hard delete not built (legacy's cascading hard-delete is the riskier of the two legacy paths — deliberately deferred, soft delete covers the common case) |
| Manual restaurant onboarding | Backend implemented | Temporary password returned once in the API response, never emailed/logged in plaintext (fixes legacy High #11) |
| Subscription plan management | Backend implemented | List + update; no UI |
| Platform user management (cross-restaurant) | Backend implemented (list only) | Create/edit/toggle/reset-password not yet exposed for platform-wide users (restaurant-scoped staff CRUD already covers per-restaurant management) |
| Platform analytics/revenue reporting | Backend implemented (basic) | Aggregate stats only; no trend/per-restaurant breakdown yet |
| Support ticket helpdesk (admin-side) | **Not built** | |
| System settings (maintenance mode, business hours) | **Not built** | |
| Database backup trigger | **Not built** | |
| Announcements (platform broadcast) | **Not built** | Schema model exists; no service/routes |
| Audit log (platform-wide) | **Not built** | |
| Superadmin console (any UI) | **Not built** | No frontend pages at all yet for this entire section |

## Cross-cutting / Infrastructure
| Feature | Status | Notes |
|---|---|---|
| Multi-tenancy isolation | Backend implemented | Real FKs + JWT-derived tenant ID only (never a request param) — fixes legacy's confirmed cross-tenant leakage bugs |
| RBAC / permission system | Backend implemented | Single DB-backed mechanism replacing legacy's 3 overlapping ones |
| Email (transactional) | **Not built** | No mail service wired up in the new backend yet |
| File uploads (menu images) | **Not built** | No upload endpoint yet |
| Real-time/notifications | Architecture designed | Polling decision documented; no notifications-list endpoint exposed yet |
| Payment gateway integration | N/A by design | None exists in legacy — explicit open business question, not being built absent confirmation |
| SMS / WhatsApp integration | N/A by design | None exists in legacy |
| 2FA | Not started | Legacy doesn't have it either — optional future improvement |
| Password reset | Not started | Legacy doesn't have it either — recommended future improvement, not a parity blocker |

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
| Plaintext password emails on restaurant onboarding | Replaced with a one-time API response value (docs/SECURITY_AUDIT.md #11) |
