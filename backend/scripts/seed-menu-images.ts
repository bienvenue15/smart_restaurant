/**
 * Dev-only convenience: backfills placeholder image URLs onto existing menu
 * items that don't have one yet, and adds a handful of new menu items with
 * placeholder photos, on Heaven Restaurant Kigali. Images are external URLs
 * (Unsplash CDN) — staff can still replace any of them via the existing
 * upload feature (POST /staff/menu/items/:id/image), which is untouched.
 *
 * Usage: npm run seed:menu-images
 */
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

const BACKFILL: Record<string, string> = {
  'Sambusa (Spring Rolls)': 'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?w=600&h=400&fit=crop',
  'Grilled Tilapia': 'https://images.unsplash.com/photo-1580476262798-bddd9f4b7369?w=600&h=400&fit=crop',
  'Beef Brochette': 'https://images.unsplash.com/photo-1529563021893-cc83c992d75d?w=600&h=400&fit=crop',
  'Pasta Alfredo': 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=600&h=400&fit=crop',
  'Isombe with Rice': 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=600&h=400&fit=crop',
  'Goat Brochette': 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&h=400&fit=crop',
  'Chocolate Mousse': 'https://images.unsplash.com/photo-1541599468348-e96984315921?w=600&h=400&fit=crop',
  'Fruit Salad': 'https://images.unsplash.com/photo-1490474418585-ba9bad8fd0ea?w=600&h=400&fit=crop',
  'Fresh Passion Juice': 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?w=600&h=400&fit=crop',
  'Rwandan Coffee': 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600&h=400&fit=crop',
  'Ikivuguto (Yogurt)': 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600&h=400&fit=crop',
};

const NEW_ITEMS: { category: string; name: string; description: string; price: number; imageUrl: string; preparationTime?: number }[] = [
  {
    category: 'Appetizers',
    name: 'Vegetable Samosa',
    description: 'Crispy pastry filled with spiced vegetables, served with tamarind sauce',
    price: 5000,
    imageUrl: 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=600&h=400&fit=crop',
  },
  {
    category: 'Appetizers',
    name: 'Garden Salad',
    description: 'Fresh mixed greens, tomatoes and cucumber with house dressing',
    price: 4500,
    imageUrl: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&h=400&fit=crop',
  },
  {
    category: 'Main Course',
    name: 'Margherita Pizza',
    description: 'Wood-fired pizza with tomato, mozzarella and fresh basil',
    price: 16000,
    imageUrl: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&h=400&fit=crop',
    preparationTime: 20,
  },
  {
    category: 'Main Course',
    name: 'Beef Burger & Chips',
    description: 'Grilled beef patty, cheddar and house sauce with a side of chips',
    price: 14000,
    imageUrl: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&h=400&fit=crop',
  },
  {
    category: 'Main Course',
    name: 'Rice and Beans',
    description: 'Classic Rwandan rice and beans with fried plantain',
    price: 9000,
    imageUrl: 'https://images.unsplash.com/photo-1596797038530-2c107229654b?w=600&h=400&fit=crop',
  },
  {
    category: 'Main Course',
    name: 'Chicken Soup',
    description: 'Slow-simmered chicken broth with vegetables',
    price: 7000,
    imageUrl: 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600&h=400&fit=crop',
  },
  {
    category: 'Beverages',
    name: 'Ginger Tea',
    description: 'Hot spiced ginger tea',
    price: 1800,
    imageUrl: 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=600&h=400&fit=crop',
  },
  {
    category: 'Beverages',
    name: 'Sparkling Water',
    description: 'Chilled sparkling water, 500ml',
    price: 1500,
    imageUrl: 'https://images.unsplash.com/photo-1523362628745-0c100150b504?w=600&h=400&fit=crop',
  },
];

async function main() {
  const restaurant = await prisma.restaurant.findFirst({ where: { name: 'Heaven Restaurant Kigali' } });
  if (!restaurant) throw new Error('Heaven Restaurant Kigali not found — run the legacy import first');

  const categories = await prisma.menuCategory.findMany({ where: { restaurantId: restaurant.id } });
  const categoryIdByName = new Map(categories.map((c) => [c.name, c.id]));

  let backfilled = 0;
  for (const [name, imageUrl] of Object.entries(BACKFILL)) {
    const result = await prisma.menuItem.updateMany({
      where: { restaurantId: restaurant.id, name, imageUrl: null },
      data: { imageUrl },
    });
    backfilled += result.count;
  }
  console.log(`Backfilled images on ${backfilled} existing menu item row(s).`);

  let created = 0;
  for (const item of NEW_ITEMS) {
    const categoryId = categoryIdByName.get(item.category);
    if (!categoryId) {
      console.warn(`  ! category "${item.category}" not found, skipped "${item.name}"`);
      continue;
    }
    const existing = await prisma.menuItem.findFirst({ where: { restaurantId: restaurant.id, name: item.name } });
    if (existing) {
      console.log(`  - "${item.name}" already exists, skipped`);
      continue;
    }
    await prisma.menuItem.create({
      data: {
        restaurantId: restaurant.id,
        categoryId,
        name: item.name,
        description: item.description,
        price: item.price,
        imageUrl: item.imageUrl,
        preparationTime: item.preparationTime ?? 15,
      },
    });
    created++;
    console.log(`  + ${item.name} (${item.category})`);
  }
  console.log(`\nAdded ${created} new menu item(s).`);
}

main()
  .catch((err) => {
    console.error(err);
    process.exitCode = 1;
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
