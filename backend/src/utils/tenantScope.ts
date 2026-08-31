/**
 * Customer order reads are scoped to the verified table session, never to a
 * client-supplied restaurant/table id. This helper is the exact Prisma `where`
 * used by getOrderForTableSession — tests lock that contract so an IDOR
 * regression (legacy SECURITY_AUDIT #6) cannot land as a silent where-clause change.
 */
export function customerOrderLookupWhere(session: { restaurantId: string; tableId: string }, orderId: string) {
  return { id: orderId, restaurantId: session.restaurantId, tableId: session.tableId };
}

export function staffResourceLookupWhere(restaurantId: string, id: string) {
  return { id, restaurantId };
}
