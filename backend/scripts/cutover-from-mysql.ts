/**
 * Legacy MySQL → PostgreSQL cutover.
 *
 * Usage:
 *   MYSQL_CUTOVER_URL=mysql://user:pass@host:3306/inovasiy_smartresto \
 *     npm run cutover:mysql            # dry run (default)
 *   ... npm run cutover:mysql -- --apply
 *
 * Prints an ID map summary. Does not migrate permissions/plans (already
 * seeded). Printed table QR codes are preserved when the legacy `qr_code`
 * column is present. See docs/DEPLOYMENT.md.
 */
import crypto from 'node:crypto';
import mysql from 'mysql2/promise';
import { PrismaClient, StaffRole } from '@prisma/client';

const APPLY = process.argv.includes('--apply');
const prisma = new PrismaClient();

function uuid(): string {
  return crypto.randomUUID();
}

function asUuid(value: unknown): string {
  const raw = String(value ?? '').trim();
  if (/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(raw)) return raw;
  return uuid();
}

function mapRole(value: unknown): StaffRole {
  const key = String(value ?? '')
    .trim()
    .toUpperCase()
    .replace(/[\s-]+/g, '_');
  const allowed: StaffRole[] = ['SUPER_ADMIN', 'ADMIN', 'MANAGER', 'WAITER', 'KITCHEN', 'CASHIER'];
  return (allowed as string[]).includes(key) ? (key as StaffRole) : 'WAITER';
}

function mapEnum<T extends string>(value: unknown, fallback: T): T {
  return (String(value ?? fallback).trim().toUpperCase().replace(/[\s-]+/g, '_') || fallback) as T;
}

async function rows(conn: mysql.Connection, sql: string): Promise<Record<string, unknown>[]> {
  const [result] = await conn.query(sql);
  return result as Record<string, unknown>[];
}

async function tableExists(conn: mysql.Connection, name: string): Promise<boolean> {
  const found = await rows(conn, `SHOW TABLES LIKE ${conn.escape(name)}`);
  return found.length > 0;
}

async function main() {
  const mysqlUrl = process.env.MYSQL_CUTOVER_URL;
  if (!mysqlUrl) throw new Error('MYSQL_CUTOVER_URL is required (mysql://user:pass@host:3306/db)');

  const conn = await mysql.createConnection(mysqlUrl);
  const restaurantIds = new Map<number, string>();
  const staffIds = new Map<number, string>();
  const categoryIds = new Map<number, string>();
  const menuItemIds = new Map<number, string>();
  const tableIds = new Map<number, string>();
  const orderIds = new Map<number, string>();

  const restaurants = (await tableExists(conn, 'restaurants')) ? await rows(conn, 'SELECT * FROM restaurants') : [];
  console.log(`restaurants: ${restaurants.length}`);

  for (const row of restaurants) {
    const id = asUuid(row.uuid ?? row.id);
    restaurantIds.set(Number(row.id), id);
    if (!APPLY) continue;
    await prisma.restaurant.upsert({
      where: { email: String(row.email) },
      update: {},
      create: {
        id,
        name: String(row.name ?? 'Restaurant'),
        slug: String(row.slug ?? `restaurant-${row.id}`),
        email: String(row.email ?? `resto-${row.id}@imported.local`),
        phone: row.phone ? String(row.phone) : null,
        tin: row.tin ? String(row.tin) : null,
        address: row.address ? String(row.address) : null,
        city: row.city ? String(row.city) : null,
        isActive: row.is_active === 0 || row.is_active === false ? false : true,
        maxTables: Number(row.max_tables ?? 50),
        maxUsers: Number(row.max_users ?? 20),
        taxRate: Number(row.tax_rate ?? 0),
        serviceCharge: Number(row.service_charge ?? 0),
      },
    });
  }

  const staff = (await tableExists(conn, 'staff_users')) ? await rows(conn, 'SELECT * FROM staff_users') : [];
  console.log(`staff_users: ${staff.length}`);
  for (const row of staff) {
    const id = asUuid(row.uuid ?? row.id);
    staffIds.set(Number(row.id), id);
    if (!APPLY) continue;
    const restaurantId = row.restaurant_id == null ? null : restaurantIds.get(Number(row.restaurant_id)) ?? null;
    await prisma.staffUser.upsert({
      where: { username: String(row.username) },
      update: {},
      create: {
        id,
        restaurantId,
        username: String(row.username),
        passwordHash: String(row.password_hash ?? row.password ?? ''),
        fullName: String(row.full_name ?? row.username),
        email: row.email ? String(row.email) : null,
        phone: row.phone ? String(row.phone) : null,
        role: mapRole(row.role),
        isActive: row.is_active === 0 || row.is_active === false ? false : true,
      },
    });
  }

  const categories = (await tableExists(conn, 'menu_categories')) ? await rows(conn, 'SELECT * FROM menu_categories') : [];
  console.log(`menu_categories: ${categories.length}`);
  for (const row of categories) {
    const restaurantId = restaurantIds.get(Number(row.restaurant_id));
    if (!restaurantId) continue;
    const id = asUuid(row.uuid ?? row.id);
    categoryIds.set(Number(row.id), id);
    if (!APPLY) continue;
    await prisma.menuCategory.create({
      data: {
        id,
        restaurantId,
        name: String(row.name ?? 'Category'),
        description: row.description ? String(row.description) : null,
        displayOrder: Number(row.display_order ?? 0),
        isActive: row.is_active === 0 || row.is_active === false ? false : true,
      },
    });
  }

  const items = (await tableExists(conn, 'menu_items')) ? await rows(conn, 'SELECT * FROM menu_items') : [];
  console.log(`menu_items: ${items.length}`);
  for (const row of items) {
    const restaurantId = restaurantIds.get(Number(row.restaurant_id));
    const categoryId = categoryIds.get(Number(row.category_id));
    if (!restaurantId || !categoryId) continue;
    const id = asUuid(row.uuid ?? row.id);
    menuItemIds.set(Number(row.id), id);
    if (!APPLY) continue;
    await prisma.menuItem.create({
      data: {
        id,
        restaurantId,
        categoryId,
        name: String(row.name ?? 'Item'),
        description: row.description ? String(row.description) : null,
        price: Number(row.price ?? 0),
        imageUrl: row.image_url ? String(row.image_url) : null,
        isAvailable: row.is_available === 0 || row.is_available === false ? false : true,
        preparationTime: Number(row.preparation_time ?? 15),
      },
    });
  }

  const tables = (await tableExists(conn, 'restaurant_tables')) ? await rows(conn, 'SELECT * FROM restaurant_tables') : [];
  console.log(`restaurant_tables: ${tables.length}`);
  for (const row of tables) {
    const restaurantId = restaurantIds.get(Number(row.restaurant_id));
    if (!restaurantId) continue;
    const id = asUuid(row.uuid ?? row.id);
    tableIds.set(Number(row.id), id);
    if (!APPLY) continue;
    await prisma.restaurantTable.create({
      data: {
        id,
        restaurantId,
        tableNumber: String(row.table_number ?? row.id),
        qrCode: String(row.qr_code ?? `imported-${id}`),
        seats: Number(row.seats ?? 4),
        status: mapEnum(row.status, 'AVAILABLE'),
      },
    });
  }

  const orders = (await tableExists(conn, 'orders')) ? await rows(conn, 'SELECT * FROM orders') : [];
  console.log(`orders: ${orders.length}`);
  for (const row of orders) {
    const restaurantId = restaurantIds.get(Number(row.restaurant_id));
    const tableId = tableIds.get(Number(row.table_id));
    if (!restaurantId || !tableId) continue;
    const id = asUuid(row.uuid ?? row.id);
    orderIds.set(Number(row.id), id);
    if (!APPLY) continue;
    await prisma.order.create({
      data: {
        id,
        restaurantId,
        tableId,
        orderNumber: String(row.order_number ?? `IMP-${row.id}`),
        status: mapEnum(row.status, 'PENDING'),
        totalAmount: Number(row.total_amount ?? 0),
        specialInstructions: row.special_instructions ? String(row.special_instructions) : null,
        paymentStatus: mapEnum(row.payment_status, 'UNPAID'),
        paidAmount: Number(row.paid_amount ?? 0),
        customerName: row.customer_name ? String(row.customer_name) : null,
        customerPhone: row.customer_phone ? String(row.customer_phone) : null,
        confirmedById: row.confirmed_by ? staffIds.get(Number(row.confirmed_by)) ?? null : null,
        createdByStaffId: row.created_by_staff ? staffIds.get(Number(row.created_by_staff)) ?? null : null,
      },
    });
  }

  const orderItems = (await tableExists(conn, 'order_items')) ? await rows(conn, 'SELECT * FROM order_items') : [];
  console.log(`order_items: ${orderItems.length}`);
  if (APPLY) {
    for (const row of orderItems) {
      const orderId = orderIds.get(Number(row.order_id));
      const menuItemId = menuItemIds.get(Number(row.menu_item_id));
      if (!orderId || !menuItemId) continue;
      await prisma.orderItem.create({
        data: {
          orderId,
          menuItemId,
          quantity: Number(row.quantity ?? 1),
          unitPrice: Number(row.unit_price ?? 0),
          subtotal: Number(row.subtotal ?? Number(row.unit_price ?? 0) * Number(row.quantity ?? 1)),
          status: mapEnum(row.status, 'PENDING'),
        },
      });
    }
  }

  console.log(APPLY ? 'Cutover applied.' : 'Dry run complete. Re-run with --apply to write to PostgreSQL.');
  await conn.end();
  await prisma.$disconnect();
}

main().catch(async (err) => {
  console.error(err);
  await prisma.$disconnect();
  process.exit(1);
});
