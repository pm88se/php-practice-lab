<?php

declare(strict_types=1);

namespace Practice\MySQL;

use PDO;

/**
 * SQL-001 — Atomic money transfer using real MySQL/InnoDB and PDO.
 *
 * The caller provides a PDO connection. Do not create another connection
 * inside this method: every statement must participate in the SAME transaction.
 */
final class Sql001MoneyTransfer
{
    /**
     * Transfer a strictly positive integer amount in cents.
     *
     * Both accounts must exist and be distinct. The source balance must not
     * go below zero. On failure throw an exception and leave BOTH balances
     * unchanged. On success commit the transfer before returning.
     *
     * @throws \InvalidArgumentException for invalid amount or identical IDs
     * @throws \RuntimeException if either account is absent or funds are insufficient
     */
    public function transfer(PDO $pdo, int $fromId, int $toId, int $amountCents): void
    {
        if ($amountCents <= 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }
        if ($fromId === $toId) {
            throw new \InvalidArgumentException('Wrong account ids');
        }
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE accounts
                SET balance_cents = balance_cents - :amount
                WHERE id = :id AND balance_cents >= :minimum_balance'
            );

            $stmt->execute([
                'amount' => $amountCents,
                'minimum_balance' => $amountCents,
                'id' => $fromId,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new \RuntimeException('Source account not found');
            }

            $stmt2 = $pdo->prepare(
                'UPDATE accounts
                SET balance_cents = balance_cents + :amount
                WHERE id = :id'
            );

            $stmt2->execute([
                'amount' => $amountCents,
                'id' => $toId,
            ]);

            if ($stmt2->rowCount() !== 1) {
                throw new \RuntimeException('Destination account not found');
            }

            $pdo->commit();

        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}
