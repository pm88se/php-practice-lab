# PERF-002 — JOIN + aggregation: top customers by paid-order revenue

**Target: 25–40 minutes** on the medium data profile.
This follows PERF-001 but exercises **JOIN access paths**, not simply
`WHERE + ORDER BY + LIMIT` on a single table.

## Tables

`perf_orders` (from PERF-001):

- `id BIGINT UNSIGNED PRIMARY KEY`
- `tenant_id INT UNSIGNED`
- `customer_id INT UNSIGNED`
- `status VARCHAR(16)`
- `created_at DATETIME`
- `total_cents INT UNSIGNED`

`perf_order_items` (new for PERF-002):

- `id BIGINT UNSIGNED PRIMARY KEY`
- `order_id BIGINT UNSIGNED`
- `product_id INT UNSIGNED`
- `quantity SMALLINT UNSIGNED`
- `unit_price_cents INT UNSIGNED`

Initial `perf_order_items` has **no secondary indexes** by design.
Exactly 3 order items are seeded per order.

## Business scenario

For tenants 1 and 42, find the top 20 customers by **revenue actually
represented in order lines** on paid orders in the second half of 2025.

Return `customer_id`, distinct order count and sum of `quantity *
unit_price_cents`, ordered by revenue descending then customer ID ascending.

Do not substitute `perf_orders.total_cents` for item totals: the latter
is intentionally the source of truth for this exercise.

## Setup (same database and existing MySQL Docker container)

```bash
php mysql/performance/bin/seed.php medium
php mysql/performance/bin/seed_items.php
php mysql/performance/bin/run_sql.php mysql/performance/scenarios/PERF-002/baseline.sql
```

- `seed.php` resets **only** `perf_orders`. Run `seed_items.php` after it,
  to keep the item rows consistent with the order dataset.
- `seed_items.php` resets **only** `perf_order_items`.
- Both seeders preserve any secondary indexes you created previously.
- Existing PERF-001 indexes may remain. You are free to remove or experiment
  with them; account for their impact on the current plan.

## Your assignment

1. Read both `EXPLAIN ANALYZE` plans; identify the expensive JOIN step.
2. Propose an index or SQL rewrite and test it. **All optimization SQL is yours.**
3. Check that `customer_id`, `order_count`, `revenue_cents` and ranking
   of the 20 rows remain identical. A faster **incorrect** result is not accepted.
4. Commit index DDL and any SQL rewrites to `solution.sql`.
5. Optionally fill in `observations.md` with measurements; analysis in
   the review conversation is fine.

Don't jump straight to covering indexes or force index hints without a
measured reason. The first goal is to understand how the join is executed.
No benchmark thresholds in CI.
