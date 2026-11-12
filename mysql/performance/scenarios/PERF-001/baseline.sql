-- PERF-001: tenant order feed.
-- Run on the generated perf_orders table before adding secondary indexes.
-- Preserve these result semantics in your optimized version.
-- Task A: Recent PAID orders from a high-volume tenant.
EXPLAIN ANALYZE
SELECT id, customer_id, total_cents, created_at
FROM perf_orders
WHERE tenant_id = 1 AND status = 'paid'
ORDER BY created_at DESC, id DESC
LIMIT 50;

-- Task B: The same order feed for a lower-volume tenant.
EXPLAIN ANALYZE
SELECT id, customer_id, total_cents, created_at
FROM perf_orders
WHERE tenant_id = 42 AND status = 'paid'
ORDER BY created_at DESC, id DESC
LIMIT 50;
