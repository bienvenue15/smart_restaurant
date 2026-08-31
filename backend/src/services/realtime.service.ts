import { EventEmitter } from 'node:events';
import { Client } from 'pg';
import { config } from '@/config/env';
import { prisma } from '@/config/prisma';
import { logger } from '@/utils/logger';

export interface RealtimeEvent {
  channel: 'staff' | 'customer' | 'platform';
  type: string;
  restaurantId?: string | null;
  userId?: string;
  tableId?: string;
  at: string;
}

const bus = new EventEmitter();
bus.setMaxListeners(200);

let listener: Client | null = null;

export async function startRealtimeListener(): Promise<void> {
  if (listener) return;
  try {
    listener = new Client({ connectionString: config.databaseUrl });
    await listener.connect();
    await listener.query('LISTEN smartresto_events');
    listener.on('notification', (msg) => {
      if (!msg.payload) return;
      try {
        const event = JSON.parse(msg.payload) as RealtimeEvent;
        dispatchLocal(event);
      } catch (err) {
        logger.warn({ err }, 'Ignored malformed realtime payload');
      }
    });
    listener.on('error', (err) => {
      logger.error({ err }, 'Realtime LISTEN connection error');
    });
    logger.info('Realtime LISTEN connected on channel smartresto_events');
  } catch (err) {
    logger.error({ err }, 'Failed to start realtime listener — SSE will only see in-process events');
    listener = null;
  }
}

function dispatchLocal(event: RealtimeEvent): void {
  bus.emit('event', event);
}

export async function publishRealtime(event: Omit<RealtimeEvent, 'at'>): Promise<void> {
  const payload: RealtimeEvent = { ...event, at: new Date().toISOString() };
  try {
    await prisma.$executeRaw`SELECT pg_notify('smartresto_events', ${JSON.stringify(payload)})`;
    // The LISTEN client above receives this same notification back and
    // calls dispatchLocal itself — dispatching it again here too would
    // deliver every event to every SSE listener twice (confirmed live: a
    // waiter assignment fired two identical toasts). Only fall back to a
    // direct dispatch when there's no connected listener to round-trip
    // through in the first place.
    if (!listener) dispatchLocal(payload);
  } catch (err) {
    logger.warn({ err }, 'pg_notify failed');
    dispatchLocal(payload);
  }
}

export function subscribeRealtime(onEvent: (event: RealtimeEvent) => void): () => void {
  bus.on('event', onEvent);
  return () => bus.off('event', onEvent);
}

export function eventVisibleToStaff(event: RealtimeEvent, staff: { id: string; role: string; restaurantId: string | null }): boolean {
  if (event.channel === 'customer') return false;
  if (event.channel === 'platform') return staff.role === 'SUPER_ADMIN';
  if (staff.role === 'SUPER_ADMIN') return true;
  // A staff-channel event with no restaurantId is a platform-wide broadcast
  // (e.g. a global announcement) rather than a restaurant-scoped operational
  // event — those always set one.
  if (event.restaurantId != null && event.restaurantId !== staff.restaurantId) return false;
  if (event.userId) return event.userId === staff.id;
  return true;
}

export function eventVisibleToCustomer(event: RealtimeEvent, session: { restaurantId: string; tableId: string }): boolean {
  if (event.channel !== 'customer') return false;
  // No restaurantId => every table everywhere; a restaurantId with no
  // tableId => every table at that restaurant (e.g. an announcement); both
  // set => scoped to that one table's own session (orders, waiter calls).
  if (event.restaurantId != null && event.restaurantId !== session.restaurantId) return false;
  if (event.tableId != null && event.tableId !== session.tableId) return false;
  return true;
}
