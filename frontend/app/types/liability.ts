export interface Liability {
  id: string;
  orderAmount: string;
  liabilityCreatedAt: string;
  liabilityClearedAt: string | null;
  status: 'ACTIVE' | 'CLEARED' | 'LOSS' | 'WAIVED' | 'INVESTIGATING';
  notes: string | null;
  waiter: { fullName: string };
  order: { orderNumber: string };
}

export interface LiabilityStat {
  status: string;
  count: number;
  totalAmount: number;
}

export interface WaivedByStaff {
  waiterId: string;
  fullName: string;
  username: string;
  count: number;
  totalAmount: number;
}
