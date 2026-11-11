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
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT available FROM inventory WHERE product_id = :product_id FOR UPDATE '
            );

            $stmt->execute(['product_id' => $productId]);

            $available = $stmt->fetchColumn();
            if ($available === false || (int) $available < 1) {
                $pdo->rollBack();
                return false;
            }
            $stmt2 = $pdo->prepare(
                'UPDATE inventory
                SET available = available - 1
                WHERE product_id = :product_id'
            );
            $stmt2->execute(['product_id' => $productId]);
            if ($stmt2->rowCount() !== 1) {
                throw new \RuntimeException('Failed during reservation');
            } 
            $pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}
