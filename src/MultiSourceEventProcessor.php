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
    public static function stream(
    array $paths,
    callable $onOpen,
    callable $onClose
): \Generator {
    $generators = [];

    foreach ($paths as $source => $path) {
        $generators[$source] = self::readSource(
            $source,
            $path,
            $onOpen,
            $onClose
        );
    }

    $activeSource = 'payments';
    $paused = [];
    $completed = [];
    $needsAdvance = [];
    $total = 0;

    while (true) {
        $generator = $generators[$activeSource];

        // Повернулися до генератора, подія якого вже оброблена.
        if ($needsAdvance[$activeSource] ?? false) {
            $generator->next();
            $needsAdvance[$activeSource] = false;
        }

        // Джерело завершилося.
        if (!$generator->valid()) {
            $completed[$activeSource] = true;

            if (empty($paused)) {
                break;
            }

            $activeSource = array_pop($paused);
            continue;
        }

        $event = $generator->current();

        // Цю подію вже взято в обробку.
        $needsAdvance[$activeSource] = true;

        if (isset($event['switch_to'])) {
            $target = $event['switch_to'];

            if (!array_key_exists($target, $generators)) {
                throw new \InvalidArgumentException(
                    "Unknown source: {$target}"
                );
            }

            // Немає сенсу перемикатися на себе
            // або на вже завершене джерело.
            if (
                $target === $activeSource
                || isset($completed[$target])
            ) {
                continue;
            }

            // Якщо target уже призупинений,
            // прибираємо старий запис зі стека.
            $paused = array_values(array_filter(
                $paused,
                static fn(string $source): bool =>
                    $source !== $target
            ));

            $paused[] = $activeSource;
            $activeSource = $target;

            continue;
        }

        // Лише бізнес-події впливають на баланс
        // і передаються зовнішньому consumer.
        $total += $event['delta_cents'];
        yield $event;
    }

    return $total;
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
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open file');
        }
        try {
            $onOpen($path);
            while (($line = fgets($handle)) !== false) {
                $event = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

                if ($event['type'] === 'source.switch') {
                    yield ['switch_to' => $event['target']];
                    continue;
                }

                switch ($source) {
                    case 'payments':
                        if ($event['type'] === 'payment.settled') {
                            yield [
                                'source' => $source,
                                'event_id' => $event['event_id'],
                                'delta_cents' => $event['amount_cents'],
                            ];
                        }
                        break;

                    case 'refunds':
                        if ($event['type'] === 'refund.completed') {
                            yield [
                                'source' => $source,
                                'event_id' => $event['event_id'],
                                'delta_cents' => -$event['amount_cents'],
                            ];
                        }
                        break;

                    case 'adjustments':
                        if ($event['type'] === 'ledger.adjusted') {
                            yield [
                                'source' => $source,
                                'event_id' => $event['event_id'],
                                'delta_cents' => $event['delta_cents'],
                            ];
                        }
                        break;
                }
            }

        } finally {
            fclose($handle);
            $onClose($path);
        }

    }
}
