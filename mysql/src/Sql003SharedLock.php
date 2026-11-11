<?php

declare(strict_types=1);

namespace Practice\MySQL;

use PDO;

/**
 * SQL-003 — An InnoDB shared locking read.
 */
final class Sql003SharedLock
{
    /**
     * Read a product's available stock while acquiring a SHARED row lock.
     *
     * IMPORTANT: the caller starts and ends the transaction.
     * Do not begin, commit or roll back here: the lock must remain held
     * after this method returns until the caller ends its transaction.
     *
     * Use SELECT ... FOR SHARE (or LOCK IN SHARE MODE).
     * Return null if no matching product exists; otherwise return its
     * available count as an integer.
     */
    public function readAvailable(PDO $pdo, int $productId): ?int
    {
        throw new \LogicException('SQL-003 readAvailable() not implemented yet');
    }
}
