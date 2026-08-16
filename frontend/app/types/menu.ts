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
  restaurant: { id: string; name: string; currency: string };
  table: { id: string; tableNumber: string };
}

export interface OrderSummary {
  id: string;
  orderNumber: string;
  status: string;
  totalAmount: string;
  createdAt: string;
}
