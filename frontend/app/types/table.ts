export interface RestaurantTable {
  id: string;
  tableNumber: string;
  seats: number;
  status: 'AVAILABLE' | 'OCCUPIED' | 'RESERVED' | 'CLEANING';
  qrCode: string;
}
