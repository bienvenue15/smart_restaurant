export interface WaiterCall {
  id: string;
  requestType: 'ORDER' | 'ASSISTANCE' | 'BILL' | 'COMPLAINT' | 'OTHER';
  message: string | null;
  priority: 'LOW' | 'NORMAL' | 'HIGH';
  status: 'PENDING' | 'ACKNOWLEDGED' | 'COMPLETED' | 'CANCELLED';
  table: { tableNumber: string };
  assignedTo: { fullName: string } | null;
  createdAt: string;
}
