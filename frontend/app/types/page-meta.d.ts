declare module '#app' {
  interface PageMeta {
    /** Permission codes that grant access. Omitted = any logged-in restaurant staff. */
    permissions?: string[];
    /** Plan feature codes required in addition to permissions. */
    features?: string[];
    /** Restaurant staff roles allowed to open this page. Omitted = any logged-in restaurant staff. */
    roles?: Array<'ADMIN' | 'MANAGER' | 'WAITER' | 'KITCHEN' | 'CASHIER'>;
  }
}

export {};
