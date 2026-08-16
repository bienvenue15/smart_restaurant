export interface OrderItemLine {
  id: string;
  menuItemId: string;
  menuItem: { name: string };
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
  createdAt: string;
  specialInstructions: string | null;
  table: { tableNumber: string };
  items: OrderItemLine[];
}
