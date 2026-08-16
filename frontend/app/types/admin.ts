export interface PlatformStats {
  totalRestaurants: number;
  activeRestaurants: number;
  totalOrders: number;
  todayOrders: number;
  monthRevenue: number;
}

export interface AdminRestaurant {
  id: string;
  name: string;
  slug: string;
  email: string;
  isActive: boolean;
  subscriptionPlan: string;
  subscriptionEnd: string | null;
  createdAt: string;
}

export interface PlatformUser {
  id: string;
  username: string;
  fullName: string;
  email: string | null;
  role: string;
  isActive: boolean;
  restaurant: { id: string; name: string } | null;
}

export interface AdminSubscriptionPlan {
  id: string;
  planName: string;
  displayName: string;
  priceMonthly: string;
  priceYearly: string;
  maxTables: number;
  maxUsers: number;
  isActive: boolean;
}
