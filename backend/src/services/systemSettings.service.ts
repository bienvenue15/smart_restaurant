import { prisma } from '@/config/prisma';

export const MAINTENANCE_MODE_KEY = 'maintenance_mode';
export const BUSINESS_HOURS_KEY = 'business_hours';

export const DEFAULT_BUSINESS_HOURS: Record<string, string> = {
  mon: '09:00-22:00',
  tue: '09:00-22:00',
  wed: '09:00-22:00',
  thu: '09:00-22:00',
  fri: '09:00-22:00',
  sat: '09:00-23:00',
  sun: '10:00-21:00',
};

export async function getAllSettings() {
  return prisma.systemSetting.findMany({ orderBy: { settingKey: 'asc' } });
}

export async function setSetting(key: string, value: string, description?: string) {
  return prisma.systemSetting.upsert({
    where: { settingKey: key },
    create: { settingKey: key, settingValue: value, description },
    update: { settingValue: value, ...(description !== undefined ? { description } : {}) },
  });
}

async function getSetting(key: string): Promise<string | null> {
  const row = await prisma.systemSetting.findUnique({ where: { settingKey: key } });
  return row?.settingValue ?? null;
}

/**
 * Cached for a few seconds — this is checked on every single request (see
 * middleware/maintenanceMode.ts), so a per-request DB round trip is wasteful
 * when the value only ever changes via an explicit superadmin action.
 */
let maintenanceCache: { value: boolean; checkedAt: number } | null = null;
const CACHE_TTL_MS = 5000;

export async function isMaintenanceModeEnabled(): Promise<boolean> {
  const now = Date.now();
  if (maintenanceCache && now - maintenanceCache.checkedAt < CACHE_TTL_MS) {
    return maintenanceCache.value;
  }
  const value = (await getSetting(MAINTENANCE_MODE_KEY))?.toLowerCase() === 'on';
  maintenanceCache = { value, checkedAt: now };
  return value;
}

export function invalidateMaintenanceCache() {
  maintenanceCache = null;
}

export async function getBusinessHours(): Promise<Record<string, string>> {
  const raw = await getSetting(BUSINESS_HOURS_KEY);
  if (!raw) return DEFAULT_BUSINESS_HOURS;
  try {
    return { ...DEFAULT_BUSINESS_HOURS, ...JSON.parse(raw) };
  } catch {
    return DEFAULT_BUSINESS_HOURS;
  }
}

const DAY_KEYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

/** Informational only — ported from legacy `SystemSettings::isBusinessHours()`, which (like `enforceMaintenanceMode`) was defined but never actually wired into any request path. Not used to block anything here either; exposed so the UI can show "open now". */
export async function isWithinBusinessHours(now: Date = new Date()): Promise<boolean> {
  const hours = await getBusinessHours();
  const todayRange = hours[DAY_KEYS[now.getDay()]!];
  if (!todayRange || !todayRange.includes('-')) return true;

  const [open, close] = todayRange.split('-').map((s) => s.trim());
  const [openH, openM] = open!.split(':').map(Number);
  const [closeH, closeM] = close!.split(':').map(Number);
  const minutesNow = now.getHours() * 60 + now.getMinutes();
  return minutesNow >= openH! * 60 + openM! && minutesNow <= closeH! * 60 + closeM!;
}
