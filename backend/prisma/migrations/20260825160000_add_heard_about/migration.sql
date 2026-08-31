-- CreateEnum
CREATE TYPE "PlatformHeardAbout" AS ENUM ('WHATSAPP', 'FACEBOOK', 'INSTAGRAM', 'GOOGLE', 'FRIEND', 'ASSOCIATION', 'EVENT', 'SALES_VISIT', 'OTHER');

-- CreateEnum
CREATE TYPE "GuestHeardAboutChannel" AS ENUM ('WALK_IN', 'GOOGLE', 'SOCIAL', 'FRIEND', 'HOTEL', 'EVENT', 'OTHER');

-- AlterTable
ALTER TABLE "restaurants" ADD COLUMN "heard_about_us" "PlatformHeardAbout",
ADD COLUMN "heard_about_note" TEXT,
ADD COLUMN "heard_about_skipped" BOOLEAN NOT NULL DEFAULT false;

-- CreateTable
CREATE TABLE "guest_heard_about" (
    "id" UUID NOT NULL,
    "restaurant_id" UUID NOT NULL,
    "order_id" UUID NOT NULL,
    "channel" "GuestHeardAboutChannel",
    "skipped" BOOLEAN NOT NULL DEFAULT false,
    "created_at" TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "guest_heard_about_pkey" PRIMARY KEY ("id")
);

CREATE UNIQUE INDEX "guest_heard_about_order_id_key" ON "guest_heard_about"("order_id");
CREATE INDEX "guest_heard_about_restaurant_id_idx" ON "guest_heard_about"("restaurant_id");

ALTER TABLE "guest_heard_about" ADD CONSTRAINT "guest_heard_about_restaurant_id_fkey" FOREIGN KEY ("restaurant_id") REFERENCES "restaurants"("id") ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE "guest_heard_about" ADD CONSTRAINT "guest_heard_about_order_id_fkey" FOREIGN KEY ("order_id") REFERENCES "orders"("id") ON DELETE CASCADE ON UPDATE CASCADE;
