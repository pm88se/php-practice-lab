<?php

declare(strict_types=1);

namespace Practice;

/**
 * PHP-010 — Nested generators, delegation and resource lifetime.
 *
 * Event archives are JSON Lines files (one JSON object per line).
 * Only events of type "order.paid" should be emitted.
 *
 * Implement the two generator layers yourself:
 *  - paidFromFile() opens/reads/closes a single file lazily;
 *  - stream() delegates to paidFromFile() with yield from, preserving order.
 *
 * The callbacks are lightweight observability hooks for tests. They must
 * execute exactly when a file is opened / closed, not when the Generator
 * object is merely created.
 */
final class NestedPaidEventStream
{
    /**
     * @param list<string> $paths
     * @param callable(string): void $onOpen
     * @param callable(string): void $onClose
     *
     * Yield ['file' => basename, 'order_id' => string, 'amount_cents' => int]
     * for each "order.paid" event, preserving file and line ordering.
     *
     * Return (via Generator::getReturn()) the total number of yielded
     * paid events once the generator has fully completed.
     *
     * @return \Generator<int, array{file: string, order_id: string, amount_cents: int}, mixed, void, int>
     */
    public static function stream(array $paths, callable $onOpen, callable $onClose): \Generator
    {
        throw new \LogicException('PHP-010 stream not implemented');
        yield; // Keep method recognized as a generator until implemented.
    }

    /**
     * Lazy generator for one file. Open only when iteration starts.
     * Every successful fopen must be paired with fclose, even when JSON
     * parsing fails or the caller abandons a partially consumed generator.
     *
     * On successful completion return the number of emitted paid events.
     *
     * @param callable(string): void $onOpen
     * @param callable(string): void $onClose
     * @return \Generator<int, array{file: string, order_id: string, amount_cents: int}, mixed, void, int>
     */
    private static function paidFromFile(string $path, callable $onOpen, callable $onClose): \Generator
    {
        throw new \LogicException('PHP-010 paidFromFile not implemented');
        yield;
    }
}
