export interface OrderItemLine {
  id: string;
  menuItemId: string;
  menuItem: { name: string; preparationTime?: number; category?: { name: string } | null };
  quantity: number;
  unitPrice: string;
  subtotal: string;
  status: 'PENDING' | 'PREPARING' | 'READY' | 'SERVED';
  specialRequest: string | null;
}

export interface StaffOrder {
  id: string;
  orderNumber: string;
  status: 'PENDING' | 'CONFIRMED' | 'PREPARING' | 'READY' | 'SERVED' | 'COMPLETED' | 'CANCELLED';
  paymentStatus: 'UNPAID' | 'PARTIAL' | 'PAID' | 'REFUNDED';
  totalAmount: string;
  paidAmount: string;
  paidAt?: string | null;
  createdAt: string;
  confirmedAt: string | null;
  specialInstructions: string | null;
  table: { tableNumber: string };
  items: OrderItemLine[];
  createdByStaff: { id: string; fullName: string } | null;
  guestHeardAbout?: { skipped: boolean; channel: string | null; rating?: number | null; comment?: string | null } | null;
}

export interface OnShiftWaiter {
  id: string;
  fullName: string;
  activeOrders: { id: string; orderNumber: string; status: string; tableNumber: string }[];
}
