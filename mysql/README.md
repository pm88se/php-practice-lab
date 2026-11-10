# MySQL Transaction Lab (PHP + PDO)

A small, disposable **real MySQL/InnoDB** environment inside `php-practice-lab`.
SQL statements live in PHP exercise files; no Laravel, ORM or test framework.
The existing PHP-001–008 runner remains independent.

## Requirements

- PHP 8.3+ with `pdo_mysql` enabled
- Docker with Docker Compose (or an existing MySQL 8.4 database)

## Start locally

```bash
docker compose -f compose.mysql.yml up -d
docker compose -f compose.mysql.yml ps
php mysql/tests/run.php
```

The container listens only on **127.0.0.1:3307** on the host to avoid
conflicting with a local MySQL on port 3306.

If the test fails with "Connection refused", check container health and retry.
The first image startup can take a while.

## Useful commands

```bash
# Restore the known sample accounts:
php mysql/bin/reset.php

# Run the independent MySQL smoke test:
php mysql/tests/run.php

# Existing PHP exercises are unaffected:
php tests/run.php

# Stop the database, preserving lab data:
docker compose -f compose.mysql.yml down

# Remove the disposable local database volume entirely:
docker compose -f compose.mysql.yml down -v
```

### Connect to the same DB with a GUI client

- Host: `127.0.0.1`
- Port: `3307`
- Database: `practice_lab`
- Username/password: `lab` / `lab`

The credentials are **deliberately local-only**. Never reuse them on remote
servers. The reset command deliberately drops and rebuilds the `accounts`
table, so do **not** aim these scripts at a production database.

Override connection settings with `MYSQL_HOST`, `MYSQL_PORT`,
`MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` environment variables.

## Included fixture

`mysql/schema.sql` creates one InnoDB `accounts` table:

| ID | Owner | Balance (cents) |
| --- | --- | ---: |
| 1 | Alice | 10000 |
| 2 | Bob | 5000 |
| 3 | Carol | 0 |

`mysql/tests/run.php` opens **two independent PDO connections** and verifies
fixture setup, a rollback, and visibility of a committed update.

**Note:** This is a harness and smoke test, not SQL-001 itself. No transaction
exercise solutions are included.

## Next exercises

- SQL-001: transfer money atomically (commit / rollback)
- SQL-002: concurrent reservations (`SELECT ... FOR UPDATE`)
- SQL-003: isolation levels and read anomalies
- SQL-004: deadlocks and lock order
- SQL-005: lock contention and diagnosis

Each exercise gets a separate setup PR with red tests and stub PHP methods,
followed by your implementation PR. The tests will use the same real MySQL.
