/**
 * Reads the `exp` claim out of a JWT without verifying it — purely to
 * schedule a proactive refresh client-side. The server is the only party
 * that ever actually trusts this token's signature/claims for
 * authorization; this is scheduling metadata, not a security check.
 */
export function decodeJwtExpiryMs(token: string): number | null {
  const parts = token.split('.');
  if (parts.length !== 3) return null;
  try {
    const payload = JSON.parse(atob(parts[1]!.replace(/-/g, '+').replace(/_/g, '/')));
    return typeof payload.exp === 'number' ? payload.exp * 1000 : null;
  } catch {
    return null;
  }
}
