-- PERF-002: paid-order revenue from actual order lines.
-- Keep the returned customer IDs, totals, count and ordering unchanged.
-- Compare plans for a large and a small tenant on the SAME seeded dataset.
-- Both queries intentionally have no index hints or join-order hints.

-- A: large tenant
EXPLAIN ANALYZE
SELECT o.customer_id,
       COUNT(DISTINCT o.id) AS order_count,
       SUM(i.quantity * i.unit_price_cents) AS revenue_cents
FROM perf_orders AS o
JOIN perf_order_items AS i ON i.order_id = o.id
WHERE o.tenant_id = 1
  AND o.status = 'paid'
  AND o.created_at >= '2025-07-01'
  AND o.created_at < '2026-01-01'
GROUP BY o.customer_id
ORDER BY revenue_cents DESC, o.customer_id ASC
LIMIT 20;

-- B: small tenant
EXPLAIN ANALYZE
SELECT o.customer_id,
       COUNT(DISTINCT o.id) AS order_count,
       SUM(i.quantity * i.unit_price_cents) AS revenue_cents
FROM perf_orders AS o
JOIN perf_order_items AS i ON i.order_id = o.id
WHERE o.tenant_id = 42
  AND o.status = 'paid'
  AND o.created_at >= '2025-07-01'
  AND o.created_at < '2026-01-01'
GROUP BY o.customer_id
ORDER BY revenue_cents DESC, o.customer_id ASC
LIMIT 20;
