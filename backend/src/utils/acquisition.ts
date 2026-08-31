import { GuestHeardAboutChannel, PlatformHeardAbout } from '@prisma/client';

export const PLATFORM_HEARD_ABOUT = [
  PlatformHeardAbout.WHATSAPP,
  PlatformHeardAbout.FACEBOOK,
  PlatformHeardAbout.INSTAGRAM,
  PlatformHeardAbout.GOOGLE,
  PlatformHeardAbout.FRIEND,
  PlatformHeardAbout.ASSOCIATION,
  PlatformHeardAbout.EVENT,
  PlatformHeardAbout.SALES_VISIT,
  PlatformHeardAbout.OTHER,
] as const;

export const GUEST_HEARD_ABOUT = [
  GuestHeardAboutChannel.WALK_IN,
  GuestHeardAboutChannel.GOOGLE,
  GuestHeardAboutChannel.SOCIAL,
  GuestHeardAboutChannel.FRIEND,
  GuestHeardAboutChannel.HOTEL,
  GuestHeardAboutChannel.EVENT,
  GuestHeardAboutChannel.OTHER,
] as const;

const REGISTER_SRC: Record<string, PlatformHeardAbout> = {
  whatsapp: PlatformHeardAbout.WHATSAPP,
  facebook: PlatformHeardAbout.FACEBOOK,
  instagram: PlatformHeardAbout.INSTAGRAM,
  google: PlatformHeardAbout.GOOGLE,
  friend: PlatformHeardAbout.FRIEND,
  association: PlatformHeardAbout.ASSOCIATION,
  event: PlatformHeardAbout.EVENT,
  sales: PlatformHeardAbout.SALES_VISIT,
  other: PlatformHeardAbout.OTHER,
};

export function parseRegisterSource(raw: unknown): PlatformHeardAbout | undefined {
  if (typeof raw !== 'string' || raw.length === 0) return undefined;
  const mapped = REGISTER_SRC[raw.trim().toLowerCase()];
  if (mapped) return mapped;
  const upper = raw.trim().toUpperCase();
  return PLATFORM_HEARD_ABOUT.find((c) => c === upper);
}
