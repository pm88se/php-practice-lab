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
        throw new \LogicException('SQL-001 transfer() not implemented yet');
    }
}
