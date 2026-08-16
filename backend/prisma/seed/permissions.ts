import { PermissionCategory, RiskLevel, StaffRole } from '@prisma/client';

/**
 * Permission catalogue seeded from codes explicitly confirmed during the
 * Phase 0 audit (docs/CURRENT_SYSTEM_AUDIT.md §3, docs/SECURITY_AUDIT.md).
 * The legacy `permissions` table had ~40 rows; only the codes the audit
 * agents directly observed in use are seeded here — extend this list as
 * additional legacy behavior is ported in later phases, rather than
 * guessing at codes that were never confirmed.
 */
export const PERMISSIONS: {
  code: string;
  name: string;
  category: PermissionCategory;
  riskLevel: RiskLevel;
  requiresApproval?: boolean;
}[] = [
  { code: 'view_orders', name: 'View orders', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.LOW },
  { code: 'create_order', name: 'Create order', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.LOW },
  { code: 'manage_orders', name: 'Manage order status', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.MEDIUM },
  { code: 'modify_order', name: 'Modify order items', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.MEDIUM },
  { code: 'update_orders', name: 'Apply discounts to orders', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.MEDIUM },
  { code: 'void_order', name: 'Void an order', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.HIGH, requiresApproval: true },

  { code: 'accept_payment', name: 'Accept payment', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.MEDIUM },
  { code: 'verify_payment', name: 'Verify payment', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.MEDIUM },
  { code: 'refund_orders', name: 'Process refunds', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.HIGH, requiresApproval: true },
  { code: 'process_refund', name: 'Approve a refund', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.HIGH, requiresApproval: true },
  { code: 'handle_cash', name: 'Handle cash sessions', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.MEDIUM },

  { code: 'view_tables', name: 'View tables', category: PermissionCategory.TABLES, riskLevel: RiskLevel.LOW },
  { code: 'manage_tables', name: 'Manage tables', category: PermissionCategory.TABLES, riskLevel: RiskLevel.MEDIUM },
  { code: 'reserve_table', name: 'Reserve a table', category: PermissionCategory.TABLES, riskLevel: RiskLevel.LOW },
  { code: 'reset_table', name: 'Reset a table', category: PermissionCategory.TABLES, riskLevel: RiskLevel.MEDIUM },

  { code: 'view_menu', name: 'View menu', category: PermissionCategory.MENU, riskLevel: RiskLevel.LOW },
  { code: 'edit_menu', name: 'Edit menu items', category: PermissionCategory.MENU, riskLevel: RiskLevel.MEDIUM },
  { code: 'manage_menu', name: 'Manage menu (full CRUD)', category: PermissionCategory.MENU, riskLevel: RiskLevel.MEDIUM },

  { code: 'manage_staff', name: 'Manage staff accounts', category: PermissionCategory.STAFF, riskLevel: RiskLevel.HIGH },
  { code: 'approve_actions', name: 'Approve pending high-risk actions', category: PermissionCategory.STAFF, riskLevel: RiskLevel.HIGH },

  { code: 'view_reports', name: 'View reports', category: PermissionCategory.REPORTS, riskLevel: RiskLevel.LOW },
  { code: 'view_activity_log', name: 'View staff activity log', category: PermissionCategory.REPORTS, riskLevel: RiskLevel.MEDIUM },
  { code: 'view_audit_trail', name: 'View audit trail', category: PermissionCategory.REPORTS, riskLevel: RiskLevel.MEDIUM },

  { code: 'manage_settings', name: 'Manage restaurant settings', category: PermissionCategory.SYSTEM, riskLevel: RiskLevel.HIGH },
  { code: 'system_settings', name: 'Manage system-wide settings', category: PermissionCategory.SYSTEM, riskLevel: RiskLevel.CRITICAL },
  { code: 'delete_records', name: 'Hard-delete records', category: PermissionCategory.SYSTEM, riskLevel: RiskLevel.CRITICAL, requiresApproval: true },
];

/**
 * Default role → permission grants, derived from the per-role capability
 * summary in docs/CURRENT_SYSTEM_AUDIT.md §3. This is a sensible starting
 * point, not a hardcoded ceiling — the admin UI (Phase 4) should let a
 * restaurant owner adjust grants per role via the RolePermission table
 * directly, exactly as the legacy `role_permissions` table was designed to
 * be used.
 */
export const ROLE_PERMISSIONS: Record<StaffRole, string[]> = {
  [StaffRole.SUPER_ADMIN]: PERMISSIONS.map((p) => p.code),
  [StaffRole.ADMIN]: PERMISSIONS.map((p) => p.code),
  [StaffRole.MANAGER]: PERMISSIONS.filter((p) => !['manage_staff', 'system_settings'].includes(p.code)).map((p) => p.code),
  [StaffRole.WAITER]: [
    'view_orders', 'create_order', 'modify_order', 'update_orders',
    'view_tables', 'reset_table',
    'view_menu',
  ],
  [StaffRole.KITCHEN]: ['view_orders', 'modify_order', 'view_menu'],
  [StaffRole.CASHIER]: ['handle_cash', 'accept_payment', 'verify_payment', 'view_tables', 'view_menu', 'view_orders'],
};
