export interface TicketReply {
  id: string;
  message: string;
  isSuperadmin: boolean;
  senderType: 'RESTAURANT' | 'SUPPORT' | 'SYSTEM';
  staff: { fullName: string } | null;
  createdAt: string;
}

export interface SupportTicket {
  id: string;
  subject: string;
  description: string | null;
  status: 'OPEN' | 'IN_PROGRESS' | 'WAITING_CUSTOMER' | 'RESOLVED' | 'CLOSED';
  priority: 'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT';
  assignedTo: string | null;
  createdAt: string;
  updatedAt: string;
  lastResponseAt: string | null;
  restaurant?: { name: string } | null;
  staff?: { fullName: string } | null;
  _count?: { replies: number };
  replies?: TicketReply[];
}

export interface TicketListResult {
  tickets: SupportTicket[];
  total: number;
  page: number;
  totalPages: number;
}

export interface SupportMessage {
  id: string;
  subject: string;
  message: string;
  channel: string;
  status: 'NEW' | 'READ' | 'ARCHIVED';
  contactName: string | null;
  contactEmail: string | null;
  createdAt: string;
  restaurant: { name: string } | null;
}
