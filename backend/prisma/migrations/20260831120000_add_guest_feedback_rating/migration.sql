-- Optional 1–5 visit rating collected after the bill is paid.
ALTER TABLE "guest_heard_about" ADD COLUMN "rating" INTEGER;
ALTER TABLE "guest_heard_about" ADD CONSTRAINT "guest_heard_about_rating_check" CHECK ("rating" IS NULL OR ("rating" >= 1 AND "rating" <= 5));
