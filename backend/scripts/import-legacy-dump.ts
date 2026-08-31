/**
 * Legacy phpMyAdmin/MariaDB dump → PostgreSQL data import.
 *
 * Unlike cutover-from-mysql.ts (which needs a *live* MySQL connection), this
 * reads a static `.sql` dump file directly and imports every row through the
 * Prisma client (parameterized — no raw SQL is ever built from dump content).
 * No table/column is added or renamed: every legacy value is mapped onto the
 * schema exactly as it already exists, per docs/DATABASE_MIGRATION_PLAN.md.
 *
 * Usage:
 *   npm run import:legacy-dump                          # dry run (default)
 *   npm run import:legacy-dump -- --apply
 *   npm run import:legacy-dump -- --file "../db.sql" --apply
 *
 * Not imported (see docs/DATABASE_MIGRATION_PLAN.md for rationale):
 *   - `restaurants_backup`, the 5 SQL views, `order_adjustments`/
 *     `table_resets`/`support_ticket_replies` (empty in the dump)
 *   - `permissions` / `role_permissions` / `subscription_plans` (already
 *     seeded by prisma/seed/ — left authoritative, not overwritten)
 *
 * Restaurants and staff users are matched by their natural unique key
 * (email / username) and left untouched if already present — this
 * specifically protects the env-seeded platform superadmin account from
 * being overwritten by the legacy superadmin row's password hash.
 */
import fs from 'node:fs';
import path from 'node:path';
import {
  PrismaClient,
  StaffRole,
  Prisma,
} from '@prisma/client';

const APPLY = process.argv.includes('--apply');
const fileFlagIndex = process.argv.indexOf('--file');
const DUMP_PATH = path.resolve(
  fileFlagIndex !== -1 ? process.argv[fileFlagIndex + 1] : path.join(__dirname, '..', '..', 'db_restaurant (3).sql'),
);

const prisma = new PrismaClient();

// ─────────────────────────────────────────────────────────────────────────
// Dump parsing — tokenizes `INSERT INTO \`table\` (col, ...) VALUES (...), ...;`
// blocks with proper MySQL string-escape handling. No other statement type
// in the dump (CREATE TABLE, functions, views) is interpreted or executed.
// ─────────────────────────────────────────────────────────────────────────

type Row = Record<string, string | null>;
type Dump = Record<string, Row[]>;

function unescapeMysqlString(raw: string): string {
  let out = '';
  for (let i = 0; i < raw.length; i++) {
    const c = raw[i];
    if (c !== '\\' || i === raw.length - 1) {
      out += c;
      continue;
    }
    const next = raw[i + 1];
    i++;
    switch (next) {
      case '0':
        out += '\0';
        break;
      case 'b':
        out += '\b';
        break;
      case 'n':
        out += '\n';
        break;
      case 'r':
        out += '\r';
        break;
      case 't':
        out += '\t';
        break;
      case 'Z':
        out += '\x1a';
        break;
      case '%':
        out += '\\%';
        break;
      case '_':
        out += '\\_';
        break;
      default:
        out += next; // \' \" \\ and anything else: backslash is just dropped
    }
  }
  return out;
}

function parseDump(sql: string): Dump {
  const dump: Dump = {};
  const insertRe = /INSERT INTO `(\w+)`\s*\(([^)]+)\)\s*VALUES\s*/g;
  let m: RegExpExecArray | null;
  while ((m = insertRe.exec(sql))) {
    const table = m[1];
    const cols = m[2].split(',').map((c) => c.trim().replace(/`/g, ''));
    let i = insertRe.lastIndex;
    const rows: Row[] = [];
    for (;;) {
      while (/\s/.test(sql[i])) i++;
      if (sql[i] !== '(') break;
      i++;
      const values: (string | null)[] = [];
      let cur = '';
      let inStr = false;
      let sawQuote = false;
      while (/[ \t]/.test(sql[i])) i++;
      for (;;) {
        const c = sql[i];
        if (inStr) {
          if (c === '\\') {
            cur += c + sql[i + 1];
            i += 2;
            continue;
          }
          if (c === "'") {
            if (sql[i + 1] === "'") {
              cur += "'";
              i += 2;
              continue;
            }
            inStr = false;
            i++;
            continue;
          }
          cur += c;
          i++;
          continue;
        }
        if (c === "'") {
          inStr = true;
          sawQuote = true;
          i++;
          continue;
        }
        if (c === ',') {
          values.push(sawQuote ? unescapeMysqlString(cur) : cur.trim() === 'NULL' ? null : cur.trim());
          cur = '';
          sawQuote = false;
          i++;
          while (/[ \t]/.test(sql[i])) i++;
          continue;
        }
        if (c === ')') {
          values.push(sawQuote ? unescapeMysqlString(cur) : cur.trim() === 'NULL' ? null : cur.trim());
          i++;
          break;
        }
        cur += c;
        i++;
      }
      const row: Row = {};
      cols.forEach((col, idx) => {
        row[col] = values[idx] ?? null;
      });
      rows.push(row);
      while (/\s/.test(sql[i])) i++;
      if (sql[i] === ',') {
        i++;
        continue;
      }
      break;
    }
    if (!dump[table]) dump[table] = [];
    dump[table].push(...rows);
    insertRe.lastIndex = i;
  }
  return dump;
}

// ─────────────────────────────────────────────────────────────────────────
// Value coercion helpers
// ─────────────────────────────────────────────────────────────────────────

function str(v: string | null): string | null {
  return v === null ? null : v;
}
function req(v: string | null, fallback: string): string {
  return v === null ? fallback : v;
}
function bool(v: string | null, fallback = false): boolean {
  if (v === null) return fallback;
  return v === '1' || v.toLowerCase() === 'true';
}
function int(v: string | null, fallback = 0): number {
  if (v === null) return fallback;
  const n = Number.parseInt(v, 10);
  return Number.isNaN(n) ? fallback : n;
}
function decStr(v: string | null, fallback = '0'): string {
  return v === null ? fallback : v;
}
function utcDate(v: string | null): Date | null {
  if (v === null || v.trim() === '' || v === '0000-00-00' || v === '0000-00-00 00:00:00') return null;
  const iso = v.includes(' ') ? `${v.replace(' ', 'T')}Z` : `${v}T00:00:00Z`;
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? null : d;
}
function mapEnum<T extends string>(value: string | null, fallback: T): T {
  if (value === null) return fallback;
  const key = value.trim().toUpperCase().replace(/[\s-]+/g, '_');
  return (key || fallback) as T;
}
function mapRole(value: string | null): StaffRole {
  const key = (value ?? '').trim().toUpperCase().replace(/[\s-]+/g, '_');
  const allowed: StaffRole[] = ['SUPER_ADMIN', 'ADMIN', 'MANAGER', 'WAITER', 'KITCHEN', 'CASHIER'];
  return (allowed as string[]).includes(key) ? (key as StaffRole) : 'WAITER';
}
function parseJsonSafe(v: string | null): Prisma.InputJsonValue | undefined {
  if (v === null) return undefined;
  try {
    return JSON.parse(v);
  } catch {
    return undefined;
  }
}

// ─────────────────────────────────────────────────────────────────────────
// Import
// ─────────────────────────────────────────────────────────────────────────

async function main() {
  if (!fs.existsSync(DUMP_PATH)) {
    throw new Error(`Dump file not found: ${DUMP_PATH}`);
  }
  const sql = fs.readFileSync(DUMP_PATH, 'utf8');
  const dump = parseDump(sql);
  const rows = (table: string): Row[] => dump[table] ?? [];

  const restaurantIds = new Map<number, string>();
  const staffIds = new Map<number, string>();
  const staffRestaurant = new Map<number, string | null>();
  const categoryIds = new Map<number, string>();
  const menuItemIds = new Map<number, string>();
  const tableIds = new Map<number, string>();
  const orderIds = new Map<number, string>();
  const cashSessionIds = new Map<number, string>();
  const announcementIds = new Map<number, string>();

  const counts: Record<string, number> = {};
  const warn = (msg: string) => console.warn(`  ! ${msg}`);

  // ── restaurants ──────────────────────────────────────────────────────
  for (const row of rows('restaurants')) {
    const legacyId = int(row.id);
    const email = req(row.email, `resto-${legacyId}@imported.local`);
    if (!APPLY) {
      restaurantIds.set(legacyId, row.uuid ?? '');
      continue;
    }
    const result = await prisma.restaurant.upsert({
      where: { email },
      update: {},
      create: {
        id: row.uuid ?? undefined,
        name: req(row.name, `Restaurant ${legacyId}`),
        slug: req(row.slug, `restaurant-${legacyId}`),
        email,
        phone: str(row.phone),
        tin: str(row.tin),
        address: str(row.address),
        city: str(row.city),
        country: req(row.country, 'Rwanda'),
        currency: req(row.currency, 'RWF'),
        timezone: req(row.timezone, 'Africa/Kigali'),
        logoUrl: str(row.logo_url),
        primaryColor: req(row.primary_color, '#2563eb'),
        secondaryColor: req(row.secondary_color, '#1e40af'),
        taxRate: decStr(row.tax_rate),
        serviceCharge: decStr(row.service_charge),
        isActive: bool(row.is_active, true),
        maxTables: int(row.max_tables, 50),
        maxUsers: int(row.max_users, 20),
        subscriptionPlan: mapEnum(row.subscription_plan, 'TRIAL'),
        subscriptionStart: utcDate(row.subscription_start),
        subscriptionEnd: utcDate(row.subscription_end),
        createdAt: utcDate(row.created_at) ?? undefined,
        updatedAt: utcDate(row.updated_at) ?? undefined,
      },
    });
    restaurantIds.set(legacyId, result.id);
  }
  counts.restaurants = rows('restaurants').length;

  // ── staff_users ──────────────────────────────────────────────────────
  for (const row of rows('staff_users')) {
    const legacyId = int(row.id);
    const username = req(row.username, `staff-${legacyId}`);
    const restaurantId = row.restaurant_id === null ? null : restaurantIds.get(int(row.restaurant_id)) ?? null;
    if (!APPLY) {
      staffIds.set(legacyId, row.uuid ?? '');
      staffRestaurant.set(legacyId, restaurantId);
      continue;
    }
    const result = await prisma.staffUser.upsert({
      where: { username },
      update: {},
      create: {
        id: row.uuid ?? undefined,
        restaurantId,
        username,
        passwordHash: req(row.password_hash, ''),
        fullName: req(row.full_name, username),
        email: str(row.email),
        phone: str(row.phone),
        role: mapRole(row.role),
        isActive: bool(row.is_active, true),
        canHandleCash: bool(row.can_handle_cash, false),
        maxDiscountPercent: decStr(row.max_discount_percent),
        securityLevel: mapEnum(row.security_level, 'STANDARD'),
        lastLoginAt: utcDate(row.last_login),
        createdAt: utcDate(row.created_at) ?? undefined,
        updatedAt: utcDate(row.updated_at) ?? undefined,
      },
    });
    staffIds.set(legacyId, result.id);
    staffRestaurant.set(legacyId, result.restaurantId);
  }
  counts.staff_users = rows('staff_users').length;

  // ── menu_categories ──────────────────────────────────────────────────
  for (const row of rows('menu_categories')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    if (!restaurantId) {
      warn(`menu_categories#${legacyId}: unknown restaurant_id ${row.restaurant_id}, skipped`);
      continue;
    }
    if (!APPLY) continue;
    const result = await prisma.menuCategory.create({
      data: {
        restaurantId,
        name: req(row.name, 'Category'),
        description: str(row.description),
        displayOrder: int(row.display_order),
        isActive: bool(row.is_active, true),
        createdAt: utcDate(row.created_at) ?? undefined,
      },
    });
    categoryIds.set(legacyId, result.id);
  }
  counts.menu_categories = rows('menu_categories').length;

  // ── menu_items ───────────────────────────────────────────────────────
  for (const row of rows('menu_items')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    const categoryId = categoryIds.get(int(row.category_id));
    if (!restaurantId || !categoryId) {
      warn(`menu_items#${legacyId}: missing restaurant/category, skipped`);
      continue;
    }
    if (!APPLY) {
      menuItemIds.set(legacyId, row.uuid ?? '');
      continue;
    }
    const result = await prisma.menuItem.create({
      data: {
        id: row.uuid ?? undefined,
        restaurantId,
        categoryId,
        name: req(row.name, 'Item'),
        description: str(row.description),
        price: decStr(row.price),
        imageUrl: str(row.image_url),
        isAvailable: bool(row.is_available, true),
        isSpecial: bool(row.is_special, false),
        displayOrder: int(row.display_order),
        preparationTime: int(row.preparation_time, 15),
        dietaryInfo: str(row.dietary_info),
        createdAt: utcDate(row.created_at) ?? undefined,
        updatedAt: utcDate(row.updated_at) ?? undefined,
      },
    });
    menuItemIds.set(legacyId, result.id);
  }
  counts.menu_items = rows('menu_items').length;

  // ── restaurant_tables ────────────────────────────────────────────────
  for (const row of rows('restaurant_tables')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    if (!restaurantId) {
      warn(`restaurant_tables#${legacyId}: unknown restaurant, skipped`);
      continue;
    }
    if (!APPLY) {
      tableIds.set(legacyId, row.uuid ?? '');
      continue;
    }
    const result = await prisma.restaurantTable.create({
      data: {
        id: row.uuid ?? undefined,
        restaurantId,
        tableNumber: req(row.table_number, String(legacyId)),
        qrCode: req(row.qr_code, `imported-${row.uuid ?? legacyId}`),
        seats: int(row.seats, 4),
        status: mapEnum(row.status, 'AVAILABLE'),
        lastOccupiedAt: utcDate(row.last_occupied_at),
        createdAt: utcDate(row.created_at) ?? undefined,
        updatedAt: utcDate(row.updated_at) ?? undefined,
      },
    });
    tableIds.set(legacyId, result.id);
  }
  counts.restaurant_tables = rows('restaurant_tables').length;

  // ── device_table_locks ───────────────────────────────────────────────
  for (const row of rows('device_table_locks')) {
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    const tableId = tableIds.get(int(row.table_id));
    if (!restaurantId || !tableId) continue;
    if (!APPLY) continue;
    await prisma.deviceTableLock.create({
      data: {
        restaurantId,
        tableId,
        deviceFingerprint: req(row.device_fingerprint, 'imported'),
        ipAddress: str(row.ip_address),
        userAgent: str(row.user_agent),
        lockedAt: utcDate(row.locked_at) ?? undefined,
        expiresAt: utcDate(row.expires_at) ?? new Date(),
        lastActivity: utcDate(row.last_activity) ?? undefined,
        orderId: str(row.order_id),
        isActive: bool(row.is_active, false),
      },
    });
  }
  counts.device_table_locks = rows('device_table_locks').length;

  // ── orders ───────────────────────────────────────────────────────────
  for (const row of rows('orders')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    const tableId = tableIds.get(int(row.table_id));
    if (!restaurantId || !tableId) {
      warn(`orders#${legacyId}: missing restaurant/table, skipped`);
      continue;
    }
    if (!APPLY) {
      orderIds.set(legacyId, row.uuid ?? '');
      continue;
    }
    const result = await prisma.order.create({
      data: {
        id: row.uuid ?? undefined,
        restaurantId,
        tableId,
        orderNumber: req(row.order_number, `IMP-${legacyId}`),
        status: mapEnum(row.status, 'PENDING'),
        totalAmount: decStr(row.total_amount),
        specialInstructions: str(row.special_instructions) || null,
        createdAt: utcDate(row.created_at) ?? undefined,
        updatedAt: utcDate(row.updated_at) ?? undefined,
        confirmedById: row.confirmed_by ? staffIds.get(int(row.confirmed_by)) ?? null : null,
        confirmedAt: utcDate(row.confirmed_at),
        servedById: row.served_by ? staffIds.get(int(row.served_by)) ?? null : null,
        servedAt: utcDate(row.served_at),
        paymentStatus: mapEnum(row.payment_status, 'UNPAID'),
        paymentMethod: str(row.payment_method),
        paidAmount: decStr(row.paid_amount),
        paidAt: utcDate(row.paid_at),
        paidToId: row.paid_to ? staffIds.get(int(row.paid_to)) ?? null : null,
        createdByStaffId: row.created_by_staff ? staffIds.get(int(row.created_by_staff)) ?? null : null,
        customerName: str(row.customer_name),
        customerPhone: str(row.customer_phone),
        liabilityRecorded: bool(row.liability_recorded, false),
        liabilityWaived: bool(row.liability_waived, false),
        escalationLevel: int(row.escalation_level, 0),
        firstReminderAt: utcDate(row.first_reminder_at),
        escalatedAt: utcDate(row.escalated_at),
      },
    });
    orderIds.set(legacyId, result.id);
  }
  counts.orders = rows('orders').length;

  // ── order_items ──────────────────────────────────────────────────────
  for (const row of rows('order_items')) {
    const legacyId = int(row.id);
    const orderId = orderIds.get(int(row.order_id));
    const menuItemId = menuItemIds.get(int(row.menu_item_id));
    if (!orderId || !menuItemId) {
      warn(`order_items#${legacyId}: missing order/menu_item, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.orderItem.create({
      data: {
        id: row.uuid ?? undefined,
        orderId,
        menuItemId,
        quantity: int(row.quantity, 1),
        unitPrice: decStr(row.unit_price),
        subtotal: decStr(row.subtotal),
        specialRequest: str(row.special_request) || null,
        status: mapEnum(row.status, 'PENDING'),
        createdAt: utcDate(row.created_at) ?? undefined,
      },
    });
  }
  counts.order_items = rows('order_items').length;

  // ── payments ─────────────────────────────────────────────────────────
  for (const row of rows('payments')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    const orderId = orderIds.get(int(row.order_id));
    const receivedById = row.received_by ? staffIds.get(int(row.received_by)) : undefined;
    if (!restaurantId || !orderId || !receivedById) {
      warn(`payments#${legacyId}: missing restaurant/order/receivedBy, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.payment.create({
      data: {
        id: row.uuid ?? undefined,
        restaurantId,
        orderId,
        paymentMethod: mapEnum(row.payment_method, 'CASH'),
        amount: decStr(row.amount),
        receivedAmount: decStr(row.received_amount),
        changeAmount: decStr(row.change_amount),
        receivedById,
        paymentReference: str(row.payment_reference),
        status: mapEnum(row.status, 'COMPLETED'),
        paymentDate: utcDate(row.payment_date) ?? undefined,
        verifiedById: row.verified_by ? staffIds.get(int(row.verified_by)) ?? null : null,
        verifiedAt: utcDate(row.verified_at),
        notes: str(row.notes),
      },
    });
  }
  counts.payments = rows('payments').length;

  // ── cash_sessions ────────────────────────────────────────────────────
  for (const row of rows('cash_sessions')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    const staffId = row.staff_id ? staffIds.get(int(row.staff_id)) : undefined;
    if (!restaurantId || !staffId) {
      warn(`cash_sessions#${legacyId}: missing restaurant/staff, skipped`);
      continue;
    }
    if (!APPLY) {
      cashSessionIds.set(legacyId, '');
      continue;
    }
    const result = await prisma.cashSession.create({
      data: {
        restaurantId,
        staffId,
        openingBalance: decStr(row.opening_balance),
        cashInHand: decStr(row.cash_in_hand),
        closingBalance: row.closing_balance === null ? null : decStr(row.closing_balance),
        expectedBalance: row.expected_balance === null ? null : decStr(row.expected_balance),
        variance: decStr(row.variance),
        openedAt: utcDate(row.opened_at) ?? undefined,
        closedAt: utcDate(row.closed_at),
        closedById: row.closed_by ? staffIds.get(int(row.closed_by)) ?? null : null,
        status: mapEnum(row.status, 'OPEN'),
        notes: str(row.notes) || null,
      },
    });
    cashSessionIds.set(legacyId, result.id);
  }
  counts.cash_sessions = rows('cash_sessions').length;

  // ── cash_transactions ────────────────────────────────────────────────
  for (const row of rows('cash_transactions')) {
    const legacyId = int(row.id);
    const cashSessionId = cashSessionIds.get(int(row.cash_session_id));
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    const staffId = row.staff_id ? staffIds.get(int(row.staff_id)) : undefined;
    if (!cashSessionId || !restaurantId || !staffId) {
      warn(`cash_transactions#${legacyId}: missing cash session/restaurant/staff, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.cashTransaction.create({
      data: {
        cashSessionId,
        restaurantId,
        staffId,
        transactionType: mapEnum(row.transaction_type, 'ADJUSTMENT'),
        amount: decStr(row.amount),
        orderId: row.order_id ? orderIds.get(int(row.order_id)) ?? null : null,
        description: str(row.description) || null,
        referenceNumber: str(row.reference_number) || null,
        createdAt: utcDate(row.created_at) ?? undefined,
      },
    });
  }
  counts.cash_transactions = rows('cash_transactions').length;

  // ── waiter_calls ─────────────────────────────────────────────────────
  for (const row of rows('waiter_calls')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    const tableId = tableIds.get(int(row.table_id));
    if (!restaurantId || !tableId) {
      warn(`waiter_calls#${legacyId}: missing restaurant/table, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.waiterCall.create({
      data: {
        restaurantId,
        tableId,
        requestType: mapEnum(row.request_type, 'OTHER'),
        message: str(row.message) || null,
        priority: mapEnum(row.priority, 'NORMAL'),
        status: mapEnum(row.status, 'PENDING'),
        assignedToId: row.assigned_to ? staffIds.get(int(row.assigned_to)) ?? null : null,
        assignedAt: utcDate(row.assigned_at),
        completedAt: utcDate(row.completed_at),
        createdAt: utcDate(row.created_at) ?? undefined,
      },
    });
  }
  counts.waiter_calls = rows('waiter_calls').length;

  // ── waiter_liabilities ───────────────────────────────────────────────
  for (const row of rows('waiter_liabilities')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    const orderId = orderIds.get(int(row.order_id));
    const waiterId = row.waiter_id ? staffIds.get(int(row.waiter_id)) : undefined;
    if (!restaurantId || !orderId || !waiterId) {
      warn(`waiter_liabilities#${legacyId}: missing restaurant/order/waiter, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.waiterLiability.create({
      data: {
        id: row.uuid ?? undefined,
        restaurantId,
        orderId,
        waiterId,
        orderAmount: decStr(row.order_amount),
        liabilityCreatedAt: utcDate(row.liability_created_at) ?? undefined,
        liabilityClearedAt: utcDate(row.liability_cleared_at),
        status: mapEnum(row.status, 'ACTIVE'),
        clearedById: row.cleared_by ? staffIds.get(int(row.cleared_by)) ?? null : null,
        paymentMethod: str(row.payment_method),
        notes: str(row.notes),
        deductionStatus: mapEnum(row.deduction_status, 'NONE'),
        deductedAmount: decStr(row.deducted_amount),
        deductedAt: utcDate(row.deducted_at),
      },
    });
  }
  counts.waiter_liabilities = rows('waiter_liabilities').length;

  // ── notifications (+ legacy staff_notifications merged in) ──────────
  for (const row of rows('notifications')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    const userId = row.user_id ? staffIds.get(int(row.user_id)) : undefined;
    if (!restaurantId || !userId) {
      warn(`notifications#${legacyId}: missing restaurant/user, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.notification.create({
      data: {
        restaurantId,
        userId,
        type: req(row.type, 'general'),
        title: req(row.title, ''),
        message: req(row.message, ''),
        data: parseJsonSafe(row.data),
        isRead: bool(row.is_read, false),
        readAt: utcDate(row.read_at),
        createdAt: utcDate(row.created_at) ?? undefined,
      },
    });
  }
  counts.notifications = rows('notifications').length;

  let staffNotifCount = 0;
  for (const row of rows('staff_notifications')) {
    const legacyId = int(row.id);
    const staffId = row.staff_id ? staffIds.get(int(row.staff_id)) : undefined;
    const restaurantId = staffId ? staffRestaurant.get(int(row.staff_id!)) ?? null : null;
    if (!staffId || !restaurantId) {
      warn(`staff_notifications#${legacyId}: unresolvable staff/restaurant, skipped`);
      continue;
    }
    staffNotifCount++;
    if (!APPLY) continue;
    await prisma.notification.create({
      data: {
        restaurantId,
        userId: staffId,
        type: req(row.notification_type, 'general'),
        title: req(row.title, ''),
        message: req(row.message, ''),
        orderId: row.order_id ? orderIds.get(int(row.order_id)) ?? null : null,
        isRead: bool(row.is_read, false),
        readAt: utcDate(row.read_at),
        createdAt: utcDate(row.created_at) ?? undefined,
      },
    });
  }
  counts.staff_notifications_merged = staffNotifCount;

  // ── announcements ────────────────────────────────────────────────────
  for (const row of rows('announcements')) {
    const legacyId = int(row.id);
    const restaurantId = row.restaurant_id ? restaurantIds.get(int(row.restaurant_id)) ?? null : null;
    const createdById = row.created_by ? staffIds.get(int(row.created_by)) ?? null : null;
    if (!APPLY) {
      announcementIds.set(legacyId, '');
      continue;
    }
    const result = await prisma.announcement.create({
      data: {
        title: req(row.title, 'Announcement'),
        message: req(row.message, ''),
        type: mapEnum(row.type, 'INFO'),
        targetAudience: mapEnum(row.target_audience, 'ALL'),
        priority: mapEnum(row.priority, 'NORMAL'),
        restaurantId,
        isActive: bool(row.is_active, true),
        isDismissible: bool(row.is_dismissible, true),
        startDate: utcDate(row.start_date),
        endDate: utcDate(row.end_date),
        createdById,
        createdAt: utcDate(row.created_at) ?? undefined,
        updatedAt: utcDate(row.updated_at) ?? undefined,
      },
    });
    announcementIds.set(legacyId, result.id);
  }
  counts.announcements = rows('announcements').length;

  // ── announcement_dismissals ──────────────────────────────────────────
  for (const row of rows('announcement_dismissals')) {
    const legacyId = row.id;
    const announcementId = announcementIds.get(int(row.announcement_id));
    const userId = row.user_id ? staffIds.get(int(row.user_id)) : undefined;
    if (!announcementId || !userId) {
      warn(`announcement_dismissals#${legacyId}: missing announcement/user, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.announcementDismissal
      .create({
        data: { announcementId, userId, dismissedAt: utcDate(row.dismissed_at) ?? undefined },
      })
      .catch((err) => {
        if (err instanceof Prisma.PrismaClientKnownRequestError && err.code === 'P2002') {
          warn(`announcement_dismissals#${legacyId}: duplicate, skipped`);
          return;
        }
        throw err;
      });
  }
  counts.announcement_dismissals = rows('announcement_dismissals').length;

  // ── audit_trail ──────────────────────────────────────────────────────
  for (const row of rows('audit_trail')) {
    const legacyId = int(row.id);
    const restaurantId = row.restaurant_id ? restaurantIds.get(int(row.restaurant_id)) ?? null : null;
    const staffId = row.staff_id ? staffIds.get(int(row.staff_id)) : undefined;
    if (!staffId) {
      warn(`audit_trail#${legacyId}: missing staff, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.auditTrail.create({
      data: {
        restaurantId,
        staffId,
        actionType: req(row.action_type, 'unknown'),
        tableName: str(row.table_name),
        recordId: str(row.record_id),
        oldValue: str(row.old_value),
        newValue: str(row.new_value),
        reason: str(row.reason) || null,
        ipAddress: str(row.ip_address),
        userAgent: str(row.user_agent),
        requiresApproval: bool(row.requires_approval, false),
        approvedById: row.approved_by ? staffIds.get(int(row.approved_by)) ?? null : null,
        approvedAt: utcDate(row.approved_at),
        status: mapEnum(row.status, 'APPROVED'),
        createdAt: utcDate(row.created_at) ?? undefined,
      },
    });
  }
  counts.audit_trail = rows('audit_trail').length;

  // ── restaurant_settings ──────────────────────────────────────────────
  for (const row of rows('restaurant_settings')) {
    const legacyId = int(row.id);
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    if (!restaurantId) {
      warn(`restaurant_settings#${legacyId}: unknown restaurant, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.restaurantSetting
      .upsert({
        where: { restaurantId_settingKey: { restaurantId, settingKey: req(row.setting_key, `setting_${legacyId}`) } },
        update: {},
        create: {
          restaurantId,
          settingKey: req(row.setting_key, `setting_${legacyId}`),
          settingValue: str(row.setting_value),
          createdAt: utcDate(row.created_at) ?? undefined,
          updatedAt: utcDate(row.updated_at) ?? undefined,
        },
      });
  }
  counts.restaurant_settings = rows('restaurant_settings').length;

  // ── system_settings (upsert-skip: don't clobber code-managed defaults) ─
  for (const row of rows('system_settings')) {
    if (!APPLY) continue;
    await prisma.systemSetting.upsert({
      where: { settingKey: req(row.setting_key, `setting_${row.id}`) },
      update: {},
      create: {
        settingKey: req(row.setting_key, `setting_${row.id}`),
        settingValue: str(row.setting_value),
        description: str(row.description),
        updatedAt: utcDate(row.updated_at) ?? undefined,
      },
    });
  }
  counts.system_settings = rows('system_settings').length;

  // ── staff_shifts ─────────────────────────────────────────────────────
  for (const row of rows('staff_shifts')) {
    const legacyId = int(row.id);
    const staffId = row.staff_id ? staffIds.get(int(row.staff_id)) : undefined;
    const restaurantId = restaurantIds.get(int(row.restaurant_id));
    if (!staffId || !restaurantId) {
      warn(`staff_shifts#${legacyId}: missing staff/restaurant, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.staffShift.create({
      data: {
        staffId,
        restaurantId,
        clockIn: utcDate(row.clock_in) ?? undefined,
        clockOut: utcDate(row.clock_out),
        status: mapEnum(row.status, 'ACTIVE'),
        notes: str(row.notes),
        createdAt: utcDate(row.created_at) ?? undefined,
        updatedAt: utcDate(row.updated_at) ?? undefined,
      },
    });
  }
  counts.staff_shifts = rows('staff_shifts').length;

  // ── staff_activity_log ───────────────────────────────────────────────
  for (const row of rows('staff_activity_log')) {
    const legacyId = int(row.id);
    const staffId = row.staff_id ? staffIds.get(int(row.staff_id)) : undefined;
    if (!staffId) {
      warn(`staff_activity_log#${legacyId}: missing staff, skipped`);
      continue;
    }
    if (!APPLY) continue;
    await prisma.staffActivityLog.create({
      data: {
        staffId,
        action: req(row.action, 'unknown'),
        description: str(row.description),
        ipAddress: str(row.ip_address),
        userAgent: str(row.user_agent),
        createdAt: utcDate(row.created_at) ?? undefined,
      },
    });
  }
  counts.staff_activity_log = rows('staff_activity_log').length;

  // ── support_messages ─────────────────────────────────────────────────
  for (const row of rows('support_messages')) {
    const restaurantId = row.restaurant_id ? restaurantIds.get(int(row.restaurant_id)) ?? null : null;
    if (!APPLY) continue;
    await prisma.supportMessage.create({
      data: {
        restaurantId,
        subject: req(row.subject, 'Support message'),
        message: req(row.message, ''),
        channel: mapEnum(row.channel, 'WEB'),
        status: mapEnum(row.status, 'NEW'),
        contactName: str(row.contact_name),
        contactEmail: str(row.contact_email),
        createdAt: utcDate(row.created_at) ?? undefined,
      },
    });
  }
  counts.support_messages = rows('support_messages').length;

  // ── support_tickets ──────────────────────────────────────────────────
  for (const row of rows('support_tickets')) {
    const restaurantId = row.restaurant_id ? restaurantIds.get(int(row.restaurant_id)) ?? null : null;
    const staffId = row.staff_id ? staffIds.get(int(row.staff_id)) ?? null : null;
    if (!APPLY) continue;
    await prisma.supportTicket.create({
      data: {
        restaurantId,
        staffId,
        subject: req(row.subject, 'Ticket'),
        description: str(row.description),
        status: mapEnum(row.status, 'OPEN'),
        priority: mapEnum(row.priority, 'MEDIUM'),
        channel: mapEnum(row.channel, 'IN_APP'),
        contactName: str(row.contact_name),
        contactEmail: str(row.contact_email),
        contactPhone: str(row.contact_phone),
        assignedTo: str(row.assigned_to),
        tags: str(row.tags),
        lastResponseAt: utcDate(row.last_response_at),
        nextFollowUp: utcDate(row.next_follow_up),
        createdAt: utcDate(row.created_at) ?? undefined,
        updatedAt: utcDate(row.updated_at) ?? undefined,
      },
    });
  }
  counts.support_tickets = rows('support_tickets').length;

  // ── system_notifications ─────────────────────────────────────────────
  for (const row of rows('system_notifications')) {
    if (!APPLY) continue;
    await prisma.systemNotification.create({
      data: {
        title: req(row.title, 'Notification'),
        message: req(row.message, ''),
        type: mapEnum(row.type, 'INFO'),
        context: mapEnum(row.context, 'SYSTEM'),
        linkUrl: str(row.link_url),
        icon: req(row.icon, 'bell'),
        isRead: bool(row.is_read, false),
        createdAt: utcDate(row.created_at) ?? undefined,
        readAt: utcDate(row.read_at),
      },
    });
  }
  counts.system_notifications = rows('system_notifications').length;

  console.log(`\nDump: ${DUMP_PATH}`);
  console.log(APPLY ? 'APPLY mode — data written to PostgreSQL.\n' : 'DRY RUN — no data written. Re-run with --apply.\n');
  for (const [table, n] of Object.entries(counts)) {
    console.log(`  ${table.padEnd(28)} ${n}`);
  }
}

main()
  .catch((err) => {
    console.error(err);
    process.exitCode = 1;
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
