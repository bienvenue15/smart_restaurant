# Feature Parity Checklist

Status legend: `Not audited` → `Audited` → `Architecture designed` → `Backend implemented` → `Frontend implemented` → `Tested` → `Production verified`

Updated 2026-08-16 (late Phase 3/4). Every feature was audited in Phase 0; this pass reflects actual implementation progress since then, not just intent — several rows below have a backend but no frontend yet, or vice versa, and that's called out explicitly rather than rounded up.

## Customer
| Feature | Status | Notes |
|---|---|---|
| QR scan → table resolution | Frontend implemented | Signed session token replaces legacy's fingerprint-only trust (SECURITY_AUDIT #9). Verified end-to-end against a real database including the anti-fraud device-lock rejection path. |
| Device-table locking (anti-fraud) | Frontend implemented (transparent) | Verified: a second scan from a different simulated device on the same table is correctly rejected while an active order exists |
| Menu browsing | Frontend implemented | |
| Cart | Frontend implemented | localStorage-persisted per table (legacy had no persistence — documented improvement, not a parity requirement) |
| Place order | Frontend implemented | Client never sends price; server always looks it up (fixes legacy Critical #2) — **verified against the real database with a forged price**, server correctly computed the real total |
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
| Staff login | Frontend implemented | Verified against real DB; redirects by role (staff vs superadmin) |
| Shift clock-in/out | Frontend implemented | Midnight-rollover bug fixed; shift-gating verified end-to-end (order confirmation correctly blocked until clock-in, against the real database) |
| Role-based dashboard | Frontend implemented | KPI tiles + shift toggle, verified showing real order/revenue data |
| Order management (view/confirm/complete) | Frontend implemented | Status buttons limited to exactly what the backend state machine accepts |
| Order item status (kitchen prep pipeline) | Frontend implemented | |
| Table management (CRUD, status, reset, QR link) | Frontend implemented | Copy-link button surfaces the customer menu URL per table |
| QR code generation/regeneration | Backend implemented | Opaque unguessable token (not the legacy's guessable pattern); regenerate endpoint has no dedicated button yet (only initial issuance via table creation) |
| Menu management (categories/items, availability) | Frontend implemented | Image upload still not built |
| Waiter call queue management | Frontend implemented | Race-safe accept, verified — notification to WAITER/MANAGER/ADMIN roles confirmed landing on call creation |
| Payment recording | Backend implemented | Amount reconciled against order total (fixes legacy Critical #3), verified against real DB; no dedicated payment-entry UI yet (only via order lifecycle) |
| Cash session open/close/reconciliation | Frontend implemented | 1,000 RWF discrepancy threshold |
| Waiter liability tracking | Backend implemented (list/waive/mark-loss/stats routes now exposed) | Auto-create/clear engine + management endpoints all exist; no dedicated frontend page yet |
| Two-person approval workflow (discounts/refunds) | Backend implemented | Auto-approved within a staff member's own discount limit / `process_refund` permission, otherwise queued `PENDING` for a manager to approve/reject; no frontend yet |
| Delayed-order escalation notifications | Architecture designed | Not started |
| Staff management (CRUD) | Frontend implemented | Seat-limit enforced |
| Reports (revenue, top items, dashboard) | Backend implemented, dashboard tiles Frontend implemented | Sales/top-items reports have working endpoints but no dedicated frontend page yet |
| Restaurant settings | Backend implemented | No UI yet |
| Support ticket submission (staff-side) | **Not built** | |
| Activity/audit log viewer | Backend implemented | Merges `StaffActivityLog` + `AuditTrail`, verified against real DB (shows real login/clock-in/payment history); no frontend page yet |
| Notifications (bell icon) | Backend implemented | List/mark-read/mark-all-read; verified end-to-end (order confirmation correctly notifies ADMIN/MANAGER/KITCHEN); no frontend UI yet |
| Subscription info view | Backend implemented | Available via `GET /staff/restaurants/me`; no dedicated UI page |

## Kitchen
| Feature | Status | Notes |
|---|---|---|
| Prep queue (confirmed+ orders only) | Frontend implemented | |
| Item status updates (preparing/ready) | Frontend implemented | |
| Item availability toggle ("86 an item") | Frontend implemented | Now notifies MANAGER/ADMIN on toggle, matching legacy behavior |
| Delay awareness | **Not built** | Tied to the not-yet-built escalation feature above |

## Waiter
| Feature | Status | Notes |
|---|---|---|
| Table view | Frontend implemented | Dedicated tables page now exists (shared with admin/manager table management) |
| Order confirmation (pending→confirmed) | Frontend implemented | Now triggers a real notification to kitchen, verified end-to-end |
| Waiter call handling (accept/complete) | Frontend implemented | |
| Order completion | Frontend implemented | |
| Beverage item status exception | **Not built** | Legacy lets waiters update beverage-category items directly, bypassing kitchen — not yet ported |
| Order assignment (manager-driven, off-shift guard) | **Not built** | |
| Liability accountability | Backend implemented | Management endpoints now exposed (list own liabilities); no frontend view yet |

## Administration (Superadmin)
| Feature | Status | Notes |
|---|---|---|
| Restaurant CRUD | Frontend implemented (soft delete/deactivate only) | Hard delete not built (legacy's cascading hard-delete is the riskier of the two legacy paths — deliberately deferred, soft delete covers the common case) |
| Manual restaurant onboarding | Frontend implemented | Temporary password shown once in the UI, never emailed/logged in plaintext (fixes legacy High #11) |
| Subscription plan management | Frontend implemented | Activate/deactivate; full field editing not in the UI yet (endpoint supports it) |
| Platform user management (cross-restaurant) | Frontend implemented (list only) | Create/edit/toggle/reset-password not yet exposed for platform-wide users (restaurant-scoped staff CRUD already covers per-restaurant management) |
| Platform analytics/revenue reporting | Frontend implemented (basic) | Aggregate stats only; no trend/per-restaurant breakdown yet |
| Support ticket helpdesk (admin-side) | **Not built** | |
| System settings (maintenance mode, business hours) | **Not built** | |
| Database backup trigger | **Not built** | |
| Announcements (platform broadcast) | **Not built** | Schema model exists; no service/routes |
| Audit log (platform-wide) | **Not built** | Per-restaurant activity log exists; no cross-tenant platform view |
| Superadmin console login/bootstrap | Frontend implemented | Seeded platform account (env-configurable credentials), verified end-to-end: role claim, real aggregated stats, and — critically — a regular restaurant ADMIN account confirmed **rejected (403)** from every `/admin/*` endpoint |

## Cross-cutting / Infrastructure
| Feature | Status | Notes |
|---|---|---|
| Multi-tenancy isolation | Backend implemented | Real FKs + JWT-derived tenant ID only (never a request param) — fixes legacy's confirmed cross-tenant leakage bugs; role-isolation additionally verified for the superadmin boundary specifically |
| RBAC / permission system | Backend implemented | Single DB-backed mechanism replacing legacy's 3 overlapping ones; shift-gating verified end-to-end |
| Email (transactional) | **Not built** | No mail service wired up in the new backend yet |
| File uploads (menu images) | Frontend implemented | Real magic-byte content sniffing (not client-reported type), verified against real DB with both a rejected fake file and an accepted real PNG; served with a scoped CORP override so the frontend (different origin) can render them |
| Real-time/notifications | Backend implemented (polling model) | Notifications list/mark-read endpoints exist and are verified working; no push mechanism (deferred to v1.1 per TARGET_ARCHITECTURE) |
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
| Hardcoded superadmin email backdoor | Security anti-pattern, not a feature — replaced with a normal seeded, password-checked account |
| `staff_notifications` as a separate table from `notifications` | Consolidated into one `Notification` model — see DATABASE_MIGRATION_PLAN |
| Two independent legacy plan-definition sources (hardcoded in `register.php` vs DB-driven `subscription_plans`) | Consolidated into the single DB-driven source |
| Naive regex-based ".htaccess WAF" | Not a real security control — real WAF/reverse-proxy hardening replaces it if needed |
| Plaintext password emails on restaurant onboarding | Replaced with a one-time value shown once in the UI (docs/SECURITY_AUDIT.md #11) |
| Two separate login screens/session namespaces for staff vs superadmin | One login endpoint/page, role-based post-login redirect — no functional reason for the legacy split |
