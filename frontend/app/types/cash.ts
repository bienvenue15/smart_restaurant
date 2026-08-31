export interface CashSession {
  id: string;
  openingBalance: string;
  cashInHand: string;
  closingBalance: string | null;
  expectedBalance?: string | null;
  variance?: string | number;
  status: 'OPEN' | 'CLOSED' | 'AUDITING' | 'DISCREPANCY';
  openedAt: string;
  transactions: CashTransaction[];
}

export interface CashTransaction {
  id: string;
  transactionType: string;
  amount: string;
  category: string | null;
  description: string | null;
  referenceNumber?: string | null;
  createdAt: string;
}
