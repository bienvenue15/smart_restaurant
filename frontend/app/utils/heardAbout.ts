export const PLATFORM_HEARD_ABOUT = [
  'WHATSAPP',
  'FACEBOOK',
  'INSTAGRAM',
  'GOOGLE',
  'FRIEND',
  'ASSOCIATION',
  'EVENT',
  'SALES_VISIT',
  'OTHER',
] as const;

export const GUEST_HEARD_ABOUT = ['WALK_IN', 'GOOGLE', 'SOCIAL', 'FRIEND', 'HOTEL', 'EVENT', 'OTHER'] as const;

export type PlatformHeardAbout = (typeof PLATFORM_HEARD_ABOUT)[number];
export type GuestHeardAboutChannel = (typeof GUEST_HEARD_ABOUT)[number];

const SRC_MAP: Record<string, PlatformHeardAbout> = {
  whatsapp: 'WHATSAPP',
  facebook: 'FACEBOOK',
  instagram: 'INSTAGRAM',
  google: 'GOOGLE',
  friend: 'FRIEND',
  association: 'ASSOCIATION',
  event: 'EVENT',
  sales: 'SALES_VISIT',
  other: 'OTHER',
};

export function parseRegisterSource(raw: unknown): PlatformHeardAbout | undefined {
  if (typeof raw !== 'string' || !raw.trim()) return undefined;
  const mapped = SRC_MAP[raw.trim().toLowerCase()];
  if (mapped) return mapped;
  const upper = raw.trim().toUpperCase();
  return (PLATFORM_HEARD_ABOUT as readonly string[]).includes(upper) ? (upper as PlatformHeardAbout) : undefined;
}
