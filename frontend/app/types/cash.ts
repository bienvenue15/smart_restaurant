export interface CashSession {
  id: string;
  openingBalance: string;
  cashInHand: string;
  closingBalance: string | null;
  status: 'OPEN' | 'CLOSED' | 'AUDITING' | 'DISCREPANCY';
  openedAt: string;
  transactions: CashTransaction[];
}

export interface CashTransaction {
  id: string;
  transactionType: string;
  amount: string;
  description: string | null;
  createdAt: string;
}
