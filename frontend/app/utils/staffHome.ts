import type { StaffSession } from '~/composables/useAuthStore';

/**
 * Where a staff member should land after login, or when a route guard
 * bounces them. Kitchen staff's job is the prep screen — dumping them on
 * the floor dashboard (table occupancy they cannot act on) is why "kitchen
 * has no kitchen".
 */
export function staffHomePath(staff: StaffSession | null | undefined): string {
  if (!staff) return '/staff/login';
  if (staff.role === 'KITCHEN' && staff.permissions?.includes('view_kitchen')) {
    return '/staff/kitchen';
  }
  return '/staff/dashboard';
}

/** Cooks always get the kitchen. Everyone else needs the plan feature. */
export function kitchenDisplayAllowed(staff: StaffSession | null | undefined): boolean {
  if (!staff?.permissions?.includes('view_kitchen')) return false;
  if (staff.role === 'KITCHEN' || staff.role === 'SUPER_ADMIN') return true;
  return staff.plan?.subscriptionActive !== false && (staff.plan?.features ?? []).includes('kitchen_display');
}
