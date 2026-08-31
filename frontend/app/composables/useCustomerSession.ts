import type { ScanSessionResult } from '~/types/menu';

/**
 * The session token itself lives only in an httpOnly cookie set by the
 * backend (see backend/src/modules/customerSession/customerSession.routes.ts)
 * — this composable only holds the display info (restaurant name, table
 * number) the UI needs, never the token itself.
 */
export function useCustomerSession() {
  const restaurant = useState<ScanSessionResult['restaurant'] | null>('customer:restaurant', () => null);
  const table = useState<ScanSessionResult['table'] | null>('customer:table', () => null);

  async function scan(qrCode: string) {
    const api = useApi();
    const result = await api.post<ScanSessionResult>('/customer/session/scan', { qrCode }, { silent: true });
    restaurant.value = result.restaurant;
    table.value = result.table;
    return result;
  }

  function clear() {
    restaurant.value = null;
    table.value = null;
  }

  return { restaurant, table, scan, clear };
}
