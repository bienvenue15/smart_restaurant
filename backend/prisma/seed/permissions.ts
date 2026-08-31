import { PermissionCategory, RiskLevel, StaffRole } from '@prisma/client';

/**
 * Permission catalogue. Role grants below are the live access-control
 * matrix — each floor job gets only the codes that job needs. Managers
 * supervise and approve; they do not take payment or open the cash drawer.
 * Cashiers take payment; they do not see the kitchen display or waiter calls.
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
  { code: 'manage_orders', name: 'Confirm and complete orders', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.MEDIUM },
  { code: 'modify_order', name: 'Update order item status', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.MEDIUM },
  { code: 'update_orders', name: 'Request or apply discounts', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.MEDIUM },
  { code: 'void_order', name: 'Void an order', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.HIGH, requiresApproval: true },
  { code: 'view_kitchen', name: 'View kitchen display', category: PermissionCategory.ORDERS, riskLevel: RiskLevel.LOW },

  { code: 'accept_payment', name: 'Record payment', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.MEDIUM },
  { code: 'verify_payment', name: 'Verify payment', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.MEDIUM },
  { code: 'refund_orders', name: 'Request a refund', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.HIGH, requiresApproval: true },
  { code: 'process_refund', name: 'Apply a refund without a second approver', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.HIGH, requiresApproval: true },
  { code: 'handle_cash', name: 'Handle cash sessions', category: PermissionCategory.PAYMENTS, riskLevel: RiskLevel.MEDIUM },

  { code: 'view_tables', name: 'View tables', category: PermissionCategory.TABLES, riskLevel: RiskLevel.LOW },
  { code: 'manage_tables', name: 'Create and delete tables', category: PermissionCategory.TABLES, riskLevel: RiskLevel.MEDIUM },
  { code: 'reserve_table', name: 'Reserve a table', category: PermissionCategory.TABLES, riskLevel: RiskLevel.LOW },
  { code: 'reset_table', name: 'Reset a table', category: PermissionCategory.TABLES, riskLevel: RiskLevel.MEDIUM },
  { code: 'handle_waiter_calls', name: 'Handle waiter calls', category: PermissionCategory.TABLES, riskLevel: RiskLevel.LOW },

  { code: 'view_menu', name: 'View menu', category: PermissionCategory.MENU, riskLevel: RiskLevel.LOW },
  { code: 'edit_menu', name: '86 / restore menu items', category: PermissionCategory.MENU, riskLevel: RiskLevel.MEDIUM },
  { code: 'manage_menu', name: 'Manage menu (full CRUD)', category: PermissionCategory.MENU, riskLevel: RiskLevel.MEDIUM },

  { code: 'manage_staff', name: 'Manage staff accounts', category: PermissionCategory.STAFF, riskLevel: RiskLevel.HIGH },
  { code: 'approve_actions', name: 'Approve pending high-risk actions', category: PermissionCategory.STAFF, riskLevel: RiskLevel.HIGH },

  { code: 'view_reports', name: 'View reports', category: PermissionCategory.REPORTS, riskLevel: RiskLevel.LOW },
  { code: 'view_activity_log', name: 'View staff activity log', category: PermissionCategory.REPORTS, riskLevel: RiskLevel.MEDIUM },
  { code: 'view_audit_trail', name: 'View audit trail', category: PermissionCategory.REPORTS, riskLevel: RiskLevel.MEDIUM },
  { code: 'view_financials', name: 'View profit & loss', category: PermissionCategory.REPORTS, riskLevel: RiskLevel.HIGH },

  { code: 'manage_settings', name: 'Manage restaurant settings', category: PermissionCategory.SYSTEM, riskLevel: RiskLevel.HIGH },
  { code: 'system_settings', name: 'Manage system-wide settings', category: PermissionCategory.SYSTEM, riskLevel: RiskLevel.CRITICAL },
  { code: 'delete_records', name: 'Hard-delete records', category: PermissionCategory.SYSTEM, riskLevel: RiskLevel.CRITICAL, requiresApproval: true },
];

const ALL_CODES = PERMISSIONS.map((p) => p.code);

export const ROLE_PERMISSIONS: Record<StaffRole, string[]> = {
  [StaffRole.SUPER_ADMIN]: ALL_CODES,
  [StaffRole.ADMIN]: ALL_CODES,

  // Floor supervisor: sees kitchen and floor, approves discounts/refunds,
  // never records payment or opens the cash drawer (cashier's job).
  [StaffRole.MANAGER]: [
    'view_orders',
    'create_order',
    'manage_orders',
    'modify_order',
    'update_orders',
    'void_order',
    'view_kitchen',
    'view_tables',
    'manage_tables',
    'reserve_table',
    'reset_table',
    'handle_waiter_calls',
    'view_menu',
    'edit_menu',
    'manage_menu',
    'approve_actions',
    'view_reports',
    'view_activity_log',
    'view_audit_trail',
    'manage_settings',
  ],

  // Service: confirm/complete tickets, request discounts (always queued),
  // handle calls and tables. Never takes payment or enters the kitchen display.
  [StaffRole.WAITER]: [
    'view_orders',
    'create_order',
    'manage_orders',
    'modify_order',
    'update_orders',
    'view_tables',
    'reset_table',
    'handle_waiter_calls',
    'view_menu',
  ],

  // Prep: kitchen display, item status, 86 items. No payments, no floor calls.
  [StaffRole.KITCHEN]: ['view_orders', 'modify_order', 'view_kitchen', 'view_menu', 'edit_menu'],

  // Money: record payment, cash drawer, same-day refunds. No kitchen, no calls, no discounts.
  [StaffRole.CASHIER]: [
    'view_orders',
    'accept_payment',
    'verify_payment',
    'refund_orders',
    'handle_cash',
    'view_tables',
    'reset_table',
    'view_menu',
  ],
};
