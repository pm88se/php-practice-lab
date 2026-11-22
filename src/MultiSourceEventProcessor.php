<?php

declare(strict_types=1);

namespace Practice;

/**
 * PHP-011 — Stateful multi-source event processor.
 *
 * Each source reads its own JSONL file lazily. A source knows its own
 * business-event filter, but knows nothing about the global balance or
 * the scheduler's control flow.
 */
final class MultiSourceEventProcessor
{
    /**
     * Start with "payments". All three paths are supplied, but files must
     * be opened ONLY when their source is first scheduled.
     *
     * Control events switch the active source; previously started sources
     * must resume from their prior position (never rewind or reopen).
     *
     * Yield normalized business records:
     * ['source' => string, 'event_id' => string, 'delta_cents' => int]
     * Return the running balance (sum of delta_cents) on full completion.
     *
     * @param array{payments: string, refunds: string, adjustments: string} $paths
     * @param callable(string): void $onOpen
     * @param callable(string): void $onClose
     * @return \Generator<int, array{source: string, event_id: string, delta_cents: int}, mixed, void, int>
     */
    public static function stream(array $paths, callable $onOpen, callable $onClose): \Generator
    {
        throw new \LogicException('PHP-011 stream not implemented');
        yield;
    }

    /**
     * Source-level JSONL reader: only source-specific business events and
     * 'source.switch' controls survive source filtering.
     *
     * Control event shape: ['switch_to' => string].
     * Business event shape: ['source' => ..., 'event_id' => ..., 'delta_cents' => ...].
     *
     * Filters:
     * - payments:   payment.settled  -> +amount_cents
     * - refunds:    refund.completed -> -amount_cents
     * - adjustments: ledger.adjusted -> delta_cents (signed)
     * - all sources: source.switch   -> switch_to target
     *
     * The scheduler, not this reader, decides what switching means.
     *
     * @param callable(string): void $onOpen
     * @param callable(string): void $onClose
     * @return \Generator<int, array<string, mixed>>
     */
    private static function readSource(
        string $source,
        string $path,
        callable $onOpen,
        callable $onClose
    ): \Generator {
        throw new \LogicException('PHP-011 readSource not implemented');
        yield;
    }
}
