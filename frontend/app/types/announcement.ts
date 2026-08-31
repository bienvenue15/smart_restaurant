export interface Announcement {
  id: string;
  title: string;
  message: string;
  type: 'INFO' | 'WARNING' | 'SUCCESS' | 'DANGER' | 'PROMOTION';
  targetAudience: 'ALL' | 'STAFF' | 'CUSTOMERS' | 'ADMINS';
  priority: 'LOW' | 'NORMAL' | 'HIGH' | 'URGENT';
  restaurantId: string | null;
  isActive: boolean;
  isDismissible: boolean;
  startDate: string | null;
  endDate: string | null;
  createdAt: string;
  restaurant?: { name: string } | null;
  _count?: { dismissals: number };
}
