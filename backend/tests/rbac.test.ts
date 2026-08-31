import { describe, expect, it } from 'vitest';
import { StaffRole } from '@prisma/client';
import { ROLE_PERMISSIONS } from '../prisma/seed/permissions';

describe('role permission matrix', () => {
  it('gives cashiers payment and cash, never kitchen or waiter calls', () => {
    const cashier = ROLE_PERMISSIONS[StaffRole.CASHIER];
    expect(cashier).toEqual(expect.arrayContaining(['accept_payment', 'handle_cash', 'refund_orders', 'reset_table']));
    expect(cashier).not.toContain('view_kitchen');
    expect(cashier).not.toContain('modify_order');
    expect(cashier).not.toContain('handle_waiter_calls');
    expect(cashier).not.toContain('update_orders');
    expect(cashier).not.toContain('manage_orders');
  });

  it('does not let managers record payment or open the cash drawer', () => {
    const manager = ROLE_PERMISSIONS[StaffRole.MANAGER];
    expect(manager).toEqual(expect.arrayContaining(['approve_actions', 'view_kitchen', 'manage_orders', 'update_orders']));
    expect(manager).not.toContain('accept_payment');
    expect(manager).not.toContain('handle_cash');
    expect(manager).not.toContain('refund_orders');
    expect(manager).not.toContain('process_refund');
  });

  it('lets waiters confirm orders and request discounts, not take payment', () => {
    const waiter = ROLE_PERMISSIONS[StaffRole.WAITER];
    expect(waiter).toEqual(expect.arrayContaining(['manage_orders', 'update_orders', 'handle_waiter_calls']));
    expect(waiter).not.toContain('accept_payment');
    expect(waiter).not.toContain('view_kitchen');
    expect(waiter).not.toContain('approve_actions');
  });

  it('scopes kitchen staff to the prep display and 86ing items', () => {
    const kitchen = ROLE_PERMISSIONS[StaffRole.KITCHEN];
    expect(kitchen).toEqual(expect.arrayContaining(['view_kitchen', 'modify_order', 'edit_menu']));
    expect(kitchen).not.toContain('accept_payment');
    expect(kitchen).not.toContain('handle_waiter_calls');
    expect(kitchen).not.toContain('manage_orders');
  });
});
