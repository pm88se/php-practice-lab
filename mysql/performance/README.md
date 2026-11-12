# MySQL Performance Sandbox

Independent `PERF-XXX` experiments in the existing MySQL 8.4 Docker lab.
These do **not** run automatically in CI and do not modify the SQL-001...
SQL-004 fixtures. Your committed artifacts are SQL changes and performance
observations, not PHP implementations.

## First-time setup

```bash
docker compose -f compose.mysql.yml up -d --wait
php mysql/performance/bin/seed.php small
php mysql/performance/bin/run_sql.php mysql/performance/scenarios/PERF-001/baseline.sql
```

Then work toward `medium` (200,000 rows) or `large` (1,000,000 rows) for
more realistic optimizer choices:

```bash
php mysql/performance/bin/seed.php medium
```

Profiles: `small` 20k, `medium` 200k, `large` 1m rows.
Seed is deterministic and batched (250 rows/transaction). **Seeding
TRUNCATEs ONLY `perf_orders` and removes all previously inserted rows**.
It preserves secondary indexes you added; use your DROP INDEX statements
when resetting the optimization baseline.

To execute any solution SQL file (including CREATE/DROP INDEX):

```bash
php mysql/performance/bin/run_sql.php mysql/performance/scenarios/PERF-001/solution.sql
```

The runner executes semicolon-delimited statements. It is intended for simple
SQL and index DDL only, not stored procedures or arbitrary SQL script parsing.
It can modify data/schema, so run it **only against your disposable lab DB**.

## Rules

- Each setup PR introduces a dataset, a query, and a clear optimization target.
- Your implementation PR changes `solution.sql` and `observations.md`.
- Commit `CREATE INDEX`, `DROP INDEX`, query rewrites and `EXPLAIN ANALYZE`.
  You own the actual experiments. Do not commit generated data.
- Compare plans, actual rows/loops and execution timings. Run several times
  on the same warmed dataset; note variation, MySQL version and profile.
- Confirm optimized queries return the same rows and order as the baseline.
- Optimize for a trade-off, not a fixed percentage: indexes add write/storage
  overhead and a full scan can sometimes be the correct choice.
- Review focuses on correctness, access paths, selectivity, sorting,
  practical index cost and scalability. CI checks syntax/smoke tests, not
  speed thresholds or 1-million-row seeding.

The performance fixture lives solely in `perf_orders`; the existing
`mysql/bin/reset.php` does not drop it. This avoids accidental interference
between the two practice tracks.
