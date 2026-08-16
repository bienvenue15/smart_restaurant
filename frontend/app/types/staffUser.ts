export interface StaffMember {
  id: string;
  username: string;
  fullName: string;
  email: string | null;
  role: 'ADMIN' | 'MANAGER' | 'WAITER' | 'KITCHEN' | 'CASHIER';
  isActive: boolean;
}
