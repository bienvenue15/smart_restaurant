export interface StaffSession {
  id: string;
  fullName: string;
  role: 'SUPER_ADMIN' | 'ADMIN' | 'MANAGER' | 'WAITER' | 'KITCHEN' | 'CASHIER';
  restaurantId: string | null;
}

/**
 * Minimal in-memory auth state shared across the app via useState (Nuxt's
 * SSR-safe reactive singleton). The access token lives only in memory (not
 * localStorage) to reduce XSS exfiltration risk; the refresh token is an
 * httpOnly cookie set by the backend and never touched by client JS.
 */
export function useAuthStore() {
  const accessToken = useState<string | null>('auth:accessToken', () => null);
  const staff = useState<StaffSession | null>('auth:staff', () => null);

  function setSession(token: string, staffSession: StaffSession) {
    accessToken.value = token;
    staff.value = staffSession;
  }

  function clearSession() {
    accessToken.value = null;
    staff.value = null;
  }

  return { accessToken, staff, setSession, clearSession };
}
