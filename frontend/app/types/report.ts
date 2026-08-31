export interface GuestComment {
  rating: number | null;
  comment: string;
  createdAt: string;
  orderNumber: string;
  tableNumber: string;
}

export interface SalesReport {
  totalRevenue: number;
  orderCount: number;
  averageOrderValue: number;
  revenueByPaymentMethod: Record<string, number>;
  revenueByDay: Record<string, number>;
  guestsBySource?: Record<string, number>;
  averageRating?: number | null;
  ratingCount?: number;
  guestComments?: GuestComment[];
}

export interface TopMenuItem {
  menuItemId: string;
  name: string;
  quantitySold: number;
  revenue: number;
}

export interface ProfitLoss {
  period: 'daily' | 'weekly' | 'monthly';
  startDate: string;
  endDate: string;
  revenue: number;
  costs: number;
  costBreakdown: {
    expenses: number;
    withdrawals: number;
    refunds: number;
    liabilityLoss: number;
    liabilityWaived: number;
  };
  net: number;
  marginPct: number;
  status: 'PROFIT' | 'LOSS' | 'BREAK_EVEN';
}
