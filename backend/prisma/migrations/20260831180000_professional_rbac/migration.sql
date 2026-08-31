-- New codes for kitchen display vs floor calls, then rebuild role_permissions
-- to the job-based matrix (cashier takes payment; manager does not; cashier
-- never sees kitchen; waiter discounts always need a second person).
INSERT INTO "permissions" ("id", "code", "name", "category", "risk_level", "requires_approval")
VALUES
  (gen_random_uuid(), 'view_kitchen', 'View kitchen display', 'ORDERS', 'LOW', false),
  (gen_random_uuid(), 'handle_waiter_calls', 'Handle waiter calls', 'TABLES', 'LOW', false)
ON CONFLICT ("code") DO UPDATE SET
  "name" = EXCLUDED."name",
  "category" = EXCLUDED."category",
  "risk_level" = EXCLUDED."risk_level";

DELETE FROM "role_permissions";

INSERT INTO "role_permissions" ("id", "role", "permission_code")
SELECT gen_random_uuid(), r.role, p.code
FROM (VALUES ('SUPER_ADMIN'::"StaffRole"), ('ADMIN'::"StaffRole")) AS r(role)
CROSS JOIN "permissions" p;

INSERT INTO "role_permissions" ("id", "role", "permission_code")
SELECT gen_random_uuid(), 'MANAGER'::"StaffRole", code
FROM unnest(ARRAY[
  'view_orders', 'create_order', 'manage_orders', 'modify_order', 'update_orders', 'void_order', 'view_kitchen',
  'view_tables', 'manage_tables', 'reserve_table', 'reset_table', 'handle_waiter_calls',
  'view_menu', 'edit_menu', 'manage_menu',
  'approve_actions',
  'view_reports', 'view_activity_log', 'view_audit_trail',
  'manage_settings'
]::text[]) AS code;

INSERT INTO "role_permissions" ("id", "role", "permission_code")
SELECT gen_random_uuid(), 'WAITER'::"StaffRole", code
FROM unnest(ARRAY[
  'view_orders', 'create_order', 'manage_orders', 'modify_order', 'update_orders',
  'view_tables', 'reset_table', 'handle_waiter_calls',
  'view_menu'
]::text[]) AS code;

INSERT INTO "role_permissions" ("id", "role", "permission_code")
SELECT gen_random_uuid(), 'KITCHEN'::"StaffRole", code
FROM unnest(ARRAY[
  'view_orders', 'modify_order', 'view_kitchen', 'view_menu', 'edit_menu'
]::text[]) AS code;

INSERT INTO "role_permissions" ("id", "role", "permission_code")
SELECT gen_random_uuid(), 'CASHIER'::"StaffRole", code
FROM unnest(ARRAY[
  'view_orders', 'accept_payment', 'verify_payment', 'refund_orders', 'handle_cash',
  'view_tables', 'reset_table', 'view_menu'
]::text[]) AS code;

UPDATE "staff_users" SET "max_discount_percent" = 0 WHERE "role" = 'WAITER';
UPDATE "staff_users" SET "can_handle_cash" = true WHERE "role" = 'CASHIER';
