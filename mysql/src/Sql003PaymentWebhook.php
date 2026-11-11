<?php

declare(strict_types=1);

namespace Practice\MySQL;

use PDO;

/**
 * SQL-003 — Idempotent payment webhook delivery.
 */
final class Sql003PaymentWebhook
{
    /**
     * Apply an incoming payment event to an account exactly once.
     *
     * Return true when the event is new and the balance was credited.
     * Return false when the same event ID was already processed, without
     * changing any balance (even if retry payload differs).
     *
     * Throw InvalidArgumentException for blank event ID or non-positive amount.
     * Throw RuntimeException for a missing target account.
     * On other SQL failures, roll back and propagate the error.
     *
     * The payment_events.event_id PRIMARY KEY is the deduplication guarantee.
     * Use the provided PDO connection and one transaction for BOTH recording
     * the event and crediting the balance. Do not use PHP-only deduplication.
     */
    public function apply(
        PDO $pdo,
        string $eventId,
        int $accountId,
        int $amountCents,
    ): bool {
        throw new \LogicException('SQL-003 apply() not implemented yet');
    }
}
