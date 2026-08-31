/** Resolve a backend-relative upload path against the public API base. */
export function resolveMediaUrl(path: string | null | undefined, apiBaseUrl: string): string | null {
  if (!path) return null;
  if (/^https?:\/\//i.test(path)) return path;
  const normalized = path.startsWith('/') ? path : `/${path}`;
  if (apiBaseUrl.startsWith('http')) {
    try {
      return `${new URL(apiBaseUrl).origin}${normalized}`;
    } catch {
      return normalized;
    }
  }
  return normalized;
}
