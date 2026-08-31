export interface RestaurantProfile {
  id: string;
  name: string;
  slug: string;
  email: string;
  phone: string | null;
  tin: string | null;
  address: string | null;
  city: string | null;
  country: string;
  currency: string;
  timezone: string;
  logoUrl: string | null;
  primaryColor: string;
  secondaryColor: string;
  taxRate: string;
  serviceCharge: string;
  isActive: boolean;
  maxTables: number;
  maxUsers: number;
  subscriptionPlan: string;
  subscriptionStart: string;
  subscriptionEnd: string;
  plan?: {
    planName: string;
    displayName: string;
    features: string[];
    limits: { maxTables: number; maxUsers: number; maxMenuItems: number; maxOrdersPerMonth: number };
    usage?: { tables: number; users: number; menuItems: number; ordersThisMonth: number };
    subscriptionActive: boolean;
    subscriptionEnd: string | null;
  };
}
