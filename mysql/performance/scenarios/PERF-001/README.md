# PERF-001 — Slow Tenant Orders Feed

**Goal:** diagnose and optimize an order-feed query, not merely add an index.

## Table

`perf_orders(id PK, tenant_id, customer_id, status, created_at, total_cents)`,
InnoDB, with **no secondary indexes initially**.

The generator gives tenant 1 roughly 40% of rows and distributes the
remaining rows across other tenants. Statuses vary, and timestamps are
not ordered by primary key. Small sample data is not a meaningful benchmark:
use `medium` first, `large` optionally.

## Problem

The application fetches the **50 newest paid orders** for a particular
tenant, sorted by `created_at DESC, id DESC`.

`baseline.sql` contains two variants: a high-volume tenant (`1`) and a
lower-volume tenant (`42`). Both must return identical results (IDs,
amounts, and order) after optimization.

## Your tasks

1. Read `EXPLAIN ANALYZE` for both queries on a generated dataset.
2. Predict the costly steps and write down your hypothesis.
3. In `solution.sql`, commit your actual index DDL (create/drop), any
   query rewrite and `EXPLAIN ANALYZE` used to test the hypothesis.
4. Record before/after plan changes, row counts, loops and several
   observed timings in `observations.md`.
5. Explain why your changes help and when the extra index may cost more
   than it saves.

**No ready-made index is given.** The task is to choose the strategy.

When ready, create `drill/PERF-001` and submit the implementation PR for
code review. Don't submit fabricated results: if you did not benchmark,
note that explicitly.
