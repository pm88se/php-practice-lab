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
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT available FROM inventory WHERE product_id = :product_id FOR SHARE '
            );

            $stmt->execute(['product_id' => $productId]);
            $available = $stmt->fetchColumn();
            if ($available === false) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return null;
            }
            return $available;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Release the shared lock by finishing the transaction successfully.
     * Do not modify inventory.
     */
    public function finishRead(PDO $pdo): void
    {
        if (!$pdo->inTransaction()) {
            throw new \RuntimeException('No transaction to commit');
        }
        $pdo->commit();
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
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE inventory
                SET available = :new_available
                WHERE product_id = :product_id'
            );
            $stmt->execute([
                'product_id' => $productId, 
                'new_available' => $newAvailable
            ]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}
