export interface MenuItem {
  id: string;
  name: string;
  description: string | null;
  price: string;
  imageUrl: string | null;
  isAvailable: boolean;
  isSpecial: boolean;
  preparationTime: number;
  dietaryInfo: string | null;
}

export interface MenuCategory {
  id: string;
  name: string;
  description: string | null;
  displayOrder: number;
  items: MenuItem[];
}

export interface CartLine {
  menuItemId: string;
  name: string;
  price: number;
  quantity: number;
  specialRequest?: string;
}

export interface ScanSessionResult {
  sessionToken: string;
  restaurant: { id: string; name: string; currency: string; logoUrl: string | null; primaryColor: string };
  table: { id: string; tableNumber: string };
}

export interface OrderItemSummary {
  id: string;
  quantity: number;
  status: string;
  subtotal: string;
  specialRequest: string | null;
  menuItem: { name: string };
}

export interface OrderSummary {
  id: string;
  orderNumber: string;
  status: string;
  paymentStatus: string;
  totalAmount: string;
  paidAmount: string;
  createdAt: string;
  items?: OrderItemSummary[];
  guestHeardAbout?: { skipped: boolean; channel: string | null; rating?: number | null; comment?: string | null } | null;
}
