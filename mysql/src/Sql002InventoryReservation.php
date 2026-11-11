<?php

declare(strict_types=1);

namespace Practice\MySQL;

use PDO;

/**
 * SQL-002 — reserve the final unit of a product safely with InnoDB.
 */
final class Sql002InventoryReservation
{
    /**
     * Reserve exactly one unit of the given product.
     *
     * Return true and commit the decrement if available > 0.
     * Return false (without any inventory change) if the stock is zero
     * or the product does not exist.
     *
     * Use a transaction and SELECT ... FOR UPDATE to lock the product row
     * before deciding whether to decrement it. Work on the provided PDO.
     *
     * Let SQL errors (including a lock wait timeout) propagate, but ensure
     * the transaction is rolled back first.
     */
    public function reserve(PDO $pdo, int $productId): bool
    {
        throw new \LogicException('SQL-002 reserve() not implemented yet');
    }
}
