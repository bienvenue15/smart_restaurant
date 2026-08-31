/**
 * Superadmin console URL prefix. Matches file-based routes under
 * `app/pages/admin/*` (`/admin/dashboard`, …). Access is enforced by
 * `admin-auth` (SUPER_ADMIN only), not by hiding the path.
 */
export function useAdminPath() {
  const base = '/admin';

  function path(suffix = '') {
    const rest = suffix.replace(/^\/+/, '');
    return rest ? `${base}/${rest}` : base;
  }

  function isAdminRoute(pathname: string) {
    return pathname === base || pathname.startsWith(`${base}/`);
  }

  return { base, path, isAdminRoute };
}
