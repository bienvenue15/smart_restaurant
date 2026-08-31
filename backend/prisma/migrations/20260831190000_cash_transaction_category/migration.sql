-- Category on cash outflows (electricity, water, supplies, …) so P&L and
-- the cashier drawer can show what the money was spent on.
ALTER TABLE "cash_transactions" ADD COLUMN "category" VARCHAR(50);
