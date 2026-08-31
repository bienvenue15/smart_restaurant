export interface RestaurantBrand {
  name: string;
  logoUrl: string | null;
  primaryColor: string;
  secondaryColor: string;
}

export interface StaffSession {
  id: string;
  fullName: string;
  role: 'SUPER_ADMIN' | 'ADMIN' | 'MANAGER' | 'WAITER' | 'KITCHEN' | 'CASHIER';
  restaurantId: string | null;
  restaurant: RestaurantBrand | null;
  permissions: string[];
  canHandleCash: boolean;
  plan: {
    name: string;
    displayName: string;
    features: string[];
    limits: { maxTables: number; maxUsers: number; maxMenuItems: number; maxOrdersPerMonth: number };
    subscriptionActive: boolean;
    subscriptionEnd: string | Date | null;
  } | null;
}

// Module-scoped (not inside the composable body) so every useAuthStore()
// caller shares the same in-flight refresh — otherwise a proactive timer
// firing at the same moment as a reactive 401-retry (or several parallel
// requests all 401ing at once) would each kick off their own POST
// /auth/refresh instead of piggy-backing on one.
let refreshPromise: Promise<boolean> | null = null;

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
    staff.value = {
      ...staffSession,
      permissions: staffSession.permissions ?? [],
      canHandleCash: staffSession.canHandleCash ?? false,
      plan: staffSession.plan ?? null,
    };
  }

  function clearSession() {
    accessToken.value = null;
    staff.value = null;
  }

  function setRestaurantBrand(brand: RestaurantBrand) {
    if (!staff.value) return;
    staff.value = { ...staff.value, restaurant: brand };
  }

  /**
   * Trades the httpOnly refresh cookie for a fresh access token. Used by
   * the cold-load restore plugin, the proactive pre-expiry timer, and the
   * reactive 401-retry in useApi.ts — all three funnel through here so a
   * concurrent call just awaits the one already in flight instead of
   * firing a second `/auth/refresh` request.
   */
  async function refreshSession(extraHeaders?: HeadersInit): Promise<boolean> {
    if (refreshPromise) return refreshPromise;
    refreshPromise = (async () => {
      const apiBase = resolveApiBase();
      try {
        const response = await fetch(`${apiBase}/auth/refresh`, {
          method: 'POST',
          credentials: 'include',
          headers: extraHeaders,
        });
        if (!response.ok) return false;
        const body = await response.json();
        if (!body?.data?.accessToken) return false;
        setSession(body.data.accessToken, body.data.staff);
        return true;
      } catch {
        return false;
      }
    })();
    try {
      return await refreshPromise;
    } finally {
      refreshPromise = null;
    }
  }

  return { accessToken, staff, setSession, setRestaurantBrand, clearSession, refreshSession };
}
