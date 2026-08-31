-- Optional defense-in-depth. Prisma typically connects as table owner, which
-- bypasses RLS unless FORCE ROW LEVEL SECURITY is set and the app role is
-- not the owner. See docs/DEPLOYMENT.md.
--
-- Do not apply blindly on the migrate user. Create a restricted role first:
--   CREATE ROLE smartresto_app LOGIN PASSWORD '...';
--   GRANT CONNECT ON DATABASE smartresto TO smartresto_app;
--   GRANT USAGE ON SCHEMA public TO smartresto_app;
--   GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO smartresto_app;

ALTER TABLE restaurants ENABLE ROW LEVEL SECURITY;
-- Example tenant policy (staff queries must SET app.restaurant_id):
-- CREATE POLICY restaurant_isolation ON orders
--   USING (restaurant_id = NULLIF(current_setting('app.restaurant_id', true), '')::uuid);
