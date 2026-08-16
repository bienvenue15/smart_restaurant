export interface DashboardStats {
  todayOrders: number;
  todayRevenue: number;
  pendingOrders: number;
  activeTables: number;
  cashInHand: number;
  pendingWaiterCalls: number;
  pendingApprovals: number;
}

export interface ShiftStatus {
  onShift: boolean;
  shift: { id: string; clockIn: string } | null;
}
