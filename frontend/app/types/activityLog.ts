export interface ActivityLogRow {
  source: 'activity' | 'audit';
  id: string;
  staffName: string;
  action: string;
  description: string | null;
  createdAt: string;
}
