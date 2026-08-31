/**
 * Permission-code checks against the grants returned on login / refresh.
 * Backend `requirePermission` is still the authority — this only hides UI
 * that would 403.
 */
export function usePermissions() {
  const auth = useAuthStore();

  function can(code: string): boolean {
    return auth.staff.value?.permissions?.includes(code) ?? false;
  }

  function canAny(...codes: string[]): boolean {
    return codes.some((code) => can(code));
  }

  function canHandleCashDrawer(): boolean {
    if (!can('handle_cash')) return false;
    const staff = auth.staff.value;
    if (!staff) return false;
    if (staff.role === 'ADMIN' || staff.role === 'SUPER_ADMIN') return true;
    return staff.canHandleCash;
  }

  return { can, canAny, canHandleCashDrawer };
}
