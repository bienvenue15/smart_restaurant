# Database Migration Plan — MySQL (legacy) → PostgreSQL + Prisma (new)

## Design decision: clean-cut UUID primary keys, not a literal port of the legacy schema

The legacy MySQL schema uses `INT AUTO_INCREMENT` as the real primary key on every table (see [CURRENT_SYSTEM_AUDIT.md](CURRENT_SYSTEM_AUDIT.md) §2 for the full forensic detail — the repo's own "UUID migration complete" docs are **inaccurate**; the dump shows integer PKs everywhere, UUID columns as unenforced nullable side-columns on only 9 of 32 tables, and 18 of 32 tables with **no enforced primary key at all**, including literal duplicate `id=0` rows).

Per instruction, the new system does **not** replicate the legacy auto-increment int scheme. Every new Prisma model uses a native UUID primary key:

```prisma
id String @id @default(uuid()) @db.Uuid
```

This also happens to be the direction the legacy team was already trying (and failing) to move in — so it's a deliberate, faithful modernization of their own intent, not a departure from it. Foreign keys throughout the new schema are real, DB-enforced `@relation` constraints (the legacy schema had only 2 of 32 tables with FK constraints — everything else relied on application code alone).

**Consequence for data migration:** since legacy int IDs are not carried forward as primary keys, any legacy `uuid`/`*_uuid` columns that are already populated (8 of 32 tables) can be reused as the new PostgreSQL UUID values directly (preserves QR codes and any external references already encoding them — see note in §6). Rows with a NULL legacy UUID, and every table that never had a UUID column at all, get a fresh `gen_random_uuid()` on import.

---

## Data-cleaning steps required *before* import (legacy MySQL side)

1. **Deduplicate `id=0` rows.** `announcement_dismissals`, `restaurant_settings`, `role_permissions`, and any other table without an enforced PK need their rows deduplicated/renumbered before a UUID can be deterministically assigned per logical row.
2. **Orphan check.** Since only `notifications` and `waiter_liabilities` had real FKs, run integrity queries for every relationship below (e.g. `order_items.menu_item_id` pointing at a deleted `menu_items` row, `staff_users.restaurant_id` pointing at a deleted restaurant) and decide per-case: drop, null out, or repoint.
3. **Backfill missing legacy UUIDs** on the 8 tables that have the column, wherever `NULL` (some `order_items.menu_item_uuid` rows are NULL today), so the import script has a stable join key from old int id → new UUID for every row.
4. **Exclude `restaurants_backup`** (empty structural clone, dead weight) and the 5 SQL views (`v_active_orders`, `v_active_waiter_liabilities`, `v_restaurant_summary`, `v_staff_performance_today`, `v_waiter_liability_summary`) — views are rebuilt as Postgres views or application queries, not Prisma models. The 1 stored function (`calculate_order_status`) and all 10 triggers are reimplemented as application/service-layer logic (see §7).

---

## Table inventory & Prisma model mapping

All models get `id`, `createdAt`, `updatedAt` (`DateTime @default(now())` / `@updatedAt`) unless noted. All `restaurant_id`-scoped tables get a real `restaurantId String @db.Uuid` FK to `Restaurant`, enforced (fixing the legacy gap). All money fields map to `Decimal @db.Decimal(10,2)`. All legacy MySQL `timestamp` columns map to `@db.Timestamptz` (data was written under `SET time_zone="+00:00"`, so treat as UTC).

| Legacy table | New Prisma model | Key changes from legacy |
|---|---|---|
| `restaurants` | `Restaurant` | UUID PK; `slug` unique; keep `taxRate`/`serviceCharge` as `Decimal(5,2)`; `subscriptionPlan` becomes a real `@relation` to `SubscriptionPlan` instead of a free enum mirroring a separate table (legacy had two independent, drifting definitions of plans — see audit) |
| `restaurants_backup` | *(dropped)* | Dead/empty clone table, not migrated |
| `staff_users` | `StaffUser` | UUID PK; `restaurantId` **nullable** (only `super_admin` has none); `role` becomes a real Postgres enum; password hash column renamed `passwordHash`, bcrypt preserved |
| `menu_categories` | `MenuCategory` | UUID PK (legacy had **no** uuid column at all); real FK to `Restaurant` |
| `menu_items` | `MenuItem` | UUID PK; real FK to `MenuCategory` and `Restaurant` (legacy `category_id` had no FK) |
| `restaurant_tables` | `RestaurantTable` | UUID PK; real FK to `Restaurant`; keep `qrCode` unique |
| `orders` | `Order` | UUID PK; real FKs to `Restaurant`, `RestaurantTable`, and nullable FKs to `StaffUser` for `confirmedBy`/`servedBy`/`paidTo`/`createdByStaff`; `status` and `paymentStatus` become real Postgres enums; **`escalationLevel` kept as Int** |
| `order_adjustments` | `OrderAdjustment` | UUID PK (legacy had none enforced); real FK to `Order` |
| `order_items` | `OrderItem` | UUID PK; real FK to `Order` and `MenuItem` (legacy had no FK on either) |
| `payments` | `Payment` | UUID PK; real FK to `Order`, `Restaurant`, and `StaffUser` (`receivedBy`/`verifiedBy`) |
| `permissions` | `Permission` | UUID PK; `code` unique (legacy relied on convention, not a constraint) |
| `role_permissions` | `RolePermission` | UUID PK; composite unique `(role, permissionCode)` (legacy allowed silent duplicates — confirmed by data); FK to `Permission.code` |
| `restaurant_settings` | `RestaurantSetting` | UUID PK; composite unique `(restaurantId, settingKey)` (legacy allowed duplicate keys per restaurant — a real bug being fixed here) |
| `staff_activity_log` | `StaffActivityLog` | UUID PK; FK to `StaffUser` |
| `staff_notifications` | *(merged into `Notification`)* | Legacy had two overlapping notification tables (`notifications` restaurant-scoped w/ JSON payload + CHECK constraint, `staff_notifications` simpler/order-linked) — consolidated into one `Notification` model with an optional `orderId` and `data Json?` |
| `staff_shifts` | `StaffShift` | UUID PK; FK to `StaffUser`, `Restaurant`; **fix the legacy midnight-rollover bug** — query by open/closed status (`clockOut IS NULL`), never `DATE(clockIn) = today` |
| `subscription_plans` | `SubscriptionPlan` | UUID PK; `planName` unique; `features` as real `Json` (legacy had no validity constraint on this one, unlike `notifications.data`) |
| `support_messages` | `SupportMessage` | UUID PK; nullable FK to `Restaurant` (anonymous/prospective contacts allowed) |
| `support_tickets` | `SupportTicket` | UUID PK; nullable FKs to `Restaurant`/`StaffUser` |
| `support_ticket_replies` | `SupportTicketReply` | UUID PK; FK to `SupportTicket` |
| `system_notifications` | `SystemNotification` | UUID PK; platform-level, no tenant FK |
| `system_settings` | `SystemSetting` | UUID PK; `settingKey` unique |
| `table_resets` | `TableReset` | UUID PK; FK to `RestaurantTable`, `StaffUser`; `previousStatus`/`newStatus` reuse the `TableStatus` enum instead of free text |
| `waiter_calls` | `WaiterCall` | UUID PK (legacy had **no** uuid column); real FKs to `Restaurant`, `RestaurantTable`, nullable `StaffUser` (`assignedTo`) |
| `waiter_liabilities` | `WaiterLiability` | UUID PK; already had real FKs in legacy (the one table that did it right) — preserved: `Restaurant`/`Order` CASCADE, `StaffUser` (`waiter`) CASCADE, `StaffUser` (`clearedBy`) SET NULL |
| `cash_sessions` | `CashSession` | UUID PK (legacy had none enforced, no uuid column); FK to `Restaurant`, `StaffUser` |
| `cash_transactions` | `CashTransaction` | UUID PK; FK to `CashSession`, `Restaurant`, `StaffUser`, nullable `Order` |
| `device_table_locks` | `DeviceTableLock` | UUID PK; FK to `Restaurant`, `RestaurantTable`, nullable `Order` |
| `notifications` | `Notification` | UUID PK; real FKs preserved (`Restaurant`, `StaffUser`); `data Json?` |
| `announcements` | `Announcement` | UUID PK; nullable FK to `Restaurant` (NULL = platform-wide) |
| `announcement_dismissals` | `AnnouncementDismissal` | UUID PK (legacy had none — dedupe required, see data-cleaning §above); composite unique `(announcementId, userId)`; FK to `Announcement`, `StaffUser` |
| `audit_trail` | `AuditTrail` | UUID PK; FK to `Restaurant` (nullable), `StaffUser` |

Not migrated as tables: `v_active_orders`, `v_active_waiter_liabilities`, `v_restaurant_summary`, `v_staff_performance_today`, `v_waiter_liability_summary` (rebuild as Postgres views or Prisma raw queries once base tables are populated).

---

## Enums (native Postgres enums via Prisma `enum` blocks)

Extracted directly from the legacy `ENUM(...)` column declarations — authoritative, not guessed:

```prisma
enum StaffRole { SUPER_ADMIN ADMIN MANAGER WAITER KITCHEN CASHIER }
enum SecurityLevel { STANDARD ELEVATED ADMIN }
enum SubscriptionPlanName { TRIAL BASIC PREMIUM ENTERPRISE }
enum TableStatus { AVAILABLE OCCUPIED RESERVED CLEANING }
enum OrderStatus { PENDING CONFIRMED PREPARING READY SERVED COMPLETED CANCELLED }
enum PaymentStatus { UNPAID PARTIAL PAID REFUNDED }
enum OrderItemStatus { PENDING PREPARING READY SERVED }
enum PaymentMethod { CASH CARD MOBILE_MONEY BANK_TRANSFER }
enum PaymentRecordStatus { PENDING COMPLETED FAILED REFUNDED }
enum AdjustmentType { DISCOUNT REFUND VOID COMP }
enum ApprovalStatus { PENDING APPROVED REJECTED }
enum PermissionCategory { ORDERS PAYMENTS TABLES MENU STAFF REPORTS SYSTEM }
enum RiskLevel { LOW MEDIUM HIGH CRITICAL }
enum ShiftStatus { ACTIVE COMPLETED BREAK }
enum WaiterCallRequestType { ORDER ASSISTANCE BILL COMPLAINT OTHER }
enum WaiterCallPriority { LOW NORMAL HIGH }
enum WaiterCallStatus { PENDING ACKNOWLEDGED COMPLETED CANCELLED }
enum LiabilityStatus { ACTIVE CLEARED LOSS WAIVED INVESTIGATING }
enum DeductionStatus { NONE PENDING DEDUCTED DISPUTED }
enum CashSessionStatus { OPEN CLOSED AUDITING DISCREPANCY }
enum CashTransactionType { SALE REFUND EXPENSE DEPOSIT WITHDRAWAL ADJUSTMENT }
enum SupportMessageChannel { EMAIL CHAT PHONE WEB }
enum SupportMessageStatus { NEW READ ARCHIVED }
enum SupportTicketStatus { OPEN IN_PROGRESS WAITING_CUSTOMER RESOLVED CLOSED }
enum SupportTicketPriority { LOW MEDIUM HIGH URGENT }
enum SupportTicketChannel { EMAIL CHAT PHONE IN_APP }
enum ReplySenderType { RESTAURANT SUPPORT SYSTEM }
enum SystemNotificationType { INFO SUCCESS WARNING DANGER }
enum SystemNotificationContext { SYSTEM BILLING SUPPORT SECURITY }
enum AnnouncementType { INFO WARNING SUCCESS DANGER PROMOTION }
enum AnnouncementAudience { ALL STAFF CUSTOMERS ADMINS }
enum AnnouncementPriority { LOW NORMAL HIGH URGENT }
```

Columns that were free-text-but-enum-like in the legacy schema (`role_permissions.role`, `staff_activity_log.action`, `notifications.type`) are kept as `String` initially rather than promoted to enums, since the legacy DB never constrained them and a follow-up grep of every literal string the PHP app assigns is needed before locking values — promote to enums once confirmed complete.

---

## Data types requiring care

- **Currency**: `Decimal @db.Decimal(10,2)` throughout (RWF-appropriate). Percentages (`taxRate`, `serviceCharge`, `maxDiscountPercent`) use `Decimal @db.Decimal(5,2)`.
- **Timestamps**: `@db.Timestamptz` everywhere, treating legacy values as UTC (dump was exported under `+00:00` session timezone). The few legacy `datetime` (non-timestamp) columns (`device_table_locks.*`, `announcements.start_date/end_date`) get the same treatment unless a spot-check of the writing PHP code shows otherwise.
- **JSON**: `notifications.data` and `subscription_plans.features` → `Json?` in Prisma, with **application-level validation on write** via Joi (the legacy `features` column had no `json_valid` DB constraint, so don't assume existing data is clean).
- **Soft deletes**: the legacy schema has none anywhere (deletion is either hard, or simulated via `isActive`/status flags). The new schema keeps the `isActive`/status-flag pattern for user-facing entities (restaurants, staff, menu items, categories) rather than introducing deletedAt everywhere — add `deletedAt DateTime?` only where the audit trail genuinely needs point-in-time recovery (recommend: `Order`, `Payment`, `WaiterLiability` — financial records).

---

## Reimplementing legacy triggers/functions as application logic

| Legacy DB object | New home |
|---|---|
| `before_*_insert` triggers (UUID generation) | Not needed — Prisma `@default(uuid())` |
| `trg_order_update_table_status` (order insert → table becomes `occupied`) | `OrderService.createOrder()`, inside the same DB transaction |
| `trg_payment_audit_log` (payment insert → audit_trail row) | `PaymentService.recordPayment()`, same transaction |
| `trg_prevent_staff_delete_with_open_session` | `StaffService.deleteStaff()` — check for an open `CashSession` before allowing delete, throw a domain error otherwise |
| `calculate_order_status()` function | `OrderService.deriveStatusFromItems()` — explicit, unit-tested pure function (the legacy version's return set didn't even cover the full `OrderStatus` enum — this is a chance to fix that) |

---

## Multi-tenancy enforcement (fixing the legacy gap)

Legacy: `restaurant_id` present on nearly every table but **not FK-enforced**, and at least 6 API endpoints trusted a client-supplied `restaurant_uuid` over session context (cross-tenant read risk — see [SECURITY_AUDIT.md](SECURITY_AUDIT.md)).

New system:
1. Every tenant-scoped Prisma model gets a real `restaurantId` FK.
2. An Express middleware resolves `restaurantId` **only** from the authenticated session/JWT — never from a request body/query param — and every service-layer query includes `WHERE restaurantId = ctx.restaurantId` as a non-optional argument (enforced by TypeScript types, not convention).
3. Consider Postgres Row-Level Security as a defense-in-depth backstop once the core schema is stable (not required for v1, but cheap insurance given the legacy system's history of exactly this bug class).

## Validation

Request payload validation uses **Joi** (not Zod), applied at the Express route layer before any service/Prisma call — mirrors where `ValidationHelper`/`ApiValidator` sat in the legacy app, but centralizes rules that were previously duplicated/inconsistent across `staff.php`/`api.php`/`register.php`/`superadmin.php`.
