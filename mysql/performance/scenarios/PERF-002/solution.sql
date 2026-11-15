-- PERF-002: YOUR SQL EXPERIMENTS
--
-- Write the index DDL and/or equivalent query rewrites yourself.
-- You may modify indexes on perf_orders and perf_order_items.
-- Preserve the result values AND sort order in baseline.sql.
--
-- Optionally include EXPLAIN ANALYZE for your final candidate.
-- Document how to roll back the changes (DROP INDEX), especially when
-- re-running experiments on the same database.
--
-- Important: PERF-001's index may already exist on perf_orders depending
-- on what you merged and ran locally. Inspect SHOW INDEX as needed.

ALTER TABLE perf_order_items
ADD CONSTRAINT FK_PersonOrder
FOREIGN KEY (order_id) REFERENCES perf_orders(id);

CREATE INDEX index_tenant_status_created
ON perf_orders (tenant_id, `status`, created_at)
