<?php

declare(strict_types=1);

namespace Practice;

/**
 * PHP-012 — Keep only the K slowest requests from a very large stream.
 *
 * Implement with a CUSTOM \SplHeap comparator. The heap must contain at
 * most $capacity entries at any point. Do not store the entire input or
 * sort every incoming request.
 */
final class SlowRequestTopK
{
    /**
     * @param int $capacity Strictly positive number of retained requests.
     */
    public function __construct(private readonly int $capacity)
    {
        throw new \LogicException('PHP-012 constructor not implemented');
    }

    /**
     * Register one completed request.
     *
     * Better candidates: longer duration; ties prefer EARLIER arrival.
     * The first request accepted has arrival sequence 0.
     */
    public function add(string $id, string $route, int $durationMs): void
    {
        throw new \LogicException('PHP-012 add not implemented');
    }

    /** Number of retained entries, never greater than capacity. */
    public function count(): int
    {
        throw new \LogicException('PHP-012 count not implemented');
    }

    /**
     * Drain retained requests ordered from SLOWEST to FASTEST.
     * Equal-duration requests are ordered by original arrival (earliest first).
     * Calling drain() a second time returns [].
     *
     * @return list<array{id: string, route: string, duration_ms: int}>
     */
    public function drain(): array
    {
        throw new \LogicException('PHP-012 drain not implemented');
    }
}

/**
 * Define a dedicated \SplHeap subclass with protected compare(mixed,mixed).
 *
 * Hint: SplHeap places its HIGHEST-RANKED element at top().
 * For bounded top-K, consider which element must be ejected first.
 */
