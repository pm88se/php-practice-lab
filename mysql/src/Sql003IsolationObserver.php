<?php

declare(strict_types=1);

namespace Practice\MySQL;

use PDO;

/**
 * SQL-003 — Observe MySQL/InnoDB consistent reads at two isolation levels.
 */
final class Sql003IsolationObserver
{
    /**
     * Read the same account balance twice inside ONE reader transaction.
     *
     * $isolation is exactly 'READ COMMITTED' or 'REPEATABLE READ'.
     * Invoke $betweenReads() exactly once AFTER the first SELECT and BEFORE
     * the second SELECT. The callback uses another PDO connection to commit
     * an update, so do not close the reader transaction around the callback.
     *
     * Return [firstBalance, secondBalance] as integer cents.
     * Both reads must be ordinary (non-locking) SELECT statements.
     *
     * @param callable(): void $betweenReads
     * @return array{int, int}
     * @throws \InvalidArgumentException for unsupported isolation levels
     */
    public function observe(
        PDO $reader,
        int $accountId,
        string $isolation,
        callable $betweenReads,
    ): array {
        throw new \LogicException('SQL-003 observe() not implemented yet');
    }
}
