<?php

declare(strict_types=1);

namespace Practice\MySQL;

use PDO;

/**
 * SQL-003 — Own the full lifecycle of InnoDB shared locks.
 */
final class Sql003SharedLock
{
    /**
     * Begin a transaction, lock the product row for SHARED reading,
     * and return its available stock. Keep the transaction open when
     * the row exists: the caller will invoke finishRead() later.
     *
     * Missing product: return null and leave no transaction open.
     * On an SQL error, roll back and rethrow.
     */
    public function beginSharedRead(PDO $pdo, int $productId): ?int
    {
        throw new \LogicException('SQL-003 beginSharedRead() not implemented');
    }

    /**
     * Release the shared lock by finishing the transaction successfully.
     * Do not modify inventory.
     */
    public function finishRead(PDO $pdo): void
    {
        throw new \LogicException('SQL-003 finishRead() not implemented');
    }

    /**
     * In a separate transaction, change the product's available stock.
     * Use an UPDATE and commit on success; roll back on SQL failure.
     * Do not catch and hide MySQL lock wait errors (1205).
     *
     * This is the competing writer, not a shared read.
     */
    public function changeAvailable(PDO $pdo, int $productId, int $newAvailable): void
    {
        throw new \LogicException('SQL-003 changeAvailable() not implemented');
    }
}
