export type AdjustmentType = 'DISCOUNT' | 'REFUND' | 'CASH_CLOSE';
export type AdjustmentStatus = 'PENDING' | 'APPROVED' | 'REJECTED';

export interface PendingCashSession {
  openingBalance: number;
  expectedBalance: number;
  closingBalance: number;
  variance: number;
  openedAt: string;
}

export interface PendingAdjustment {
  id: string;
  kind: AdjustmentType;
  orderId: string | null;
  adjustmentType: AdjustmentType;
  amount: number;
  reason: string;
  status: AdjustmentStatus;
  requestedById: string;
  requestedByName: string;
  requestedByOnShift: boolean;
  requestedByActiveOrders: { id: string; orderNumber: string; status: string; tableNumber: string }[];
  createdAt: string;
  order: {
    orderNumber: string;
    paymentStatus: string;
    totalAmount: number;
    paidAmount: number;
    tableNumber: string;
  } | null;
  cashSession?: PendingCashSession | null;
}

export interface AdjustmentResult {
  id: string;
  adjustmentType: AdjustmentType;
  amount: string | number;
  status: AdjustmentStatus;
  reason: string;
}
