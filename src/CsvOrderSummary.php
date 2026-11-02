<?php

declare(strict_types=1);

namespace Practice;

final class CsvOrderSummary
{
    /**
     * Read CSV orders and return:
     * ['paid_count' => int, 'paid_total_cents' => int]
     *
     * Expected columns: order_id,status,amount_cents.
     *
     * TODO (PHP-001): implement using bounded memory.
     *
     * @return array{paid_count: int, paid_total_cents: int}
     */
    public static function summarize(string $path): array
    {
        throw new \LogicException('PHP-001 not implemented yet');
    }
}
