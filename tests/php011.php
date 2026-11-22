<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/MultiSourceEventProcessor.php';

use Practice\MultiSourceEventProcessor;

$dir = sys_get_temp_dir() . '/php011-' . bin2hex(random_bytes(6));
if (!mkdir($dir, 0700) && !is_dir($dir)) {
    throw new RuntimeException('php011 cannot create fixtures');
}
$paths = [
    'payments' => $dir . '/payments.jsonl',
    'refunds' => $dir . '/refunds.jsonl',
    'adjustments' => $dir . '/adjustments.jsonl',
];
$write = static function (string $path, array $events): void {
    file_put_contents($path, implode("\n", array_map(
        static fn(array $event): string => json_encode($event, JSON_THROW_ON_ERROR),
        $events
    )) . "\n");
};

try {
    $write($paths['payments'], [
        ['type' => 'payment.settled', 'event_id' => 'p1', 'amount_cents' => 100],
        ['type' => 'payment.pending', 'event_id' => 'ignored', 'amount_cents' => 900],
        ['type' => 'source.switch', 'target' => 'refunds'],
        ['type' => 'payment.settled', 'event_id' => 'p2', 'amount_cents' => 50],
    ]);
    $write($paths['refunds'], [
        ['type' => 'refund.completed', 'event_id' => 'r1', 'amount_cents' => 20],
        ['type' => 'source.switch', 'target' => 'adjustments'],
        ['type' => 'refund.completed', 'event_id' => 'r2', 'amount_cents' => 5],
    ]);
    $write($paths['adjustments'], [
        ['type' => 'ledger.adjusted', 'event_id' => 'a1', 'delta_cents' => 7],
        ['type' => 'ledger.proposed', 'event_id' => 'ignored2', 'delta_cents' => 1000],
    ]);

    $opened = $closed = [];
    $onOpen = static function (string $path) use (&$opened): void {
        $opened[] = basename($path);
    };
    $onClose = static function (string $path) use (&$closed): void {
        $closed[] = basename($path);
    };

    $stream = MultiSourceEventProcessor::stream($paths, $onOpen, $onClose);
    expectSame([], $opened, 'php011 lazy at creation');
    expectSame([
        ['source' => 'payments', 'event_id' => 'p1', 'delta_cents' => 100],
        ['source' => 'refunds', 'event_id' => 'r1', 'delta_cents' => -20],
        ['source' => 'adjustments', 'event_id' => 'a1', 'delta_cents' => 7],
        ['source' => 'refunds', 'event_id' => 'r2', 'delta_cents' => -5],
        ['source' => 'payments', 'event_id' => 'p2', 'delta_cents' => 50],
    ], iterator_to_array($stream, false), 'php011 switching resumes at previous position');
    expectSame(132, $stream->getReturn(), 'php011 global balance');
    expectSame(['payments.jsonl', 'refunds.jsonl', 'adjustments.jsonl'], $opened, 'php011 each file opens once');
    $sorted = $closed;
    sort($sorted);
    expectSame(['adjustments.jsonl', 'payments.jsonl', 'refunds.jsonl'], $sorted, 'php011 all files closed');

    // Drop the top-level generator while 3 sources are suspended/open.
    $opened = $closed = [];
    $partial = MultiSourceEventProcessor::stream($paths, $onOpen, $onClose);
    $seen = [];
    foreach ($partial as $event) {
        $seen[] = $event['event_id'];
        if (count($seen) === 3) {
            break;
        }
    }
    expectSame(['p1', 'r1', 'a1'], $seen, 'php011 early stop after nested switch');
    expectSame(['payments.jsonl', 'refunds.jsonl', 'adjustments.jsonl'], $opened, 'php011 three lazy opens');
    expectSame([], $closed, 'php011 suspended sources still open');
    unset($partial);
    gc_collect_cycles();
    $sorted = $closed;
    sort($sorted);
    expectSame(['adjustments.jsonl', 'payments.jsonl', 'refunds.jsonl'], $sorted, 'php011 all suspended sources released');

    // Empty other sources must never be opened when no control switches.
    $write($paths['payments'], [
        ['type' => 'payment.settled', 'event_id' => 'solo', 'amount_cents' => 9],
    ]);
    $opened = $closed = [];
    $solo = MultiSourceEventProcessor::stream($paths, $onOpen, $onClose);
    expectSame([
        ['source' => 'payments', 'event_id' => 'solo', 'delta_cents' => 9],
    ], iterator_to_array($solo, false), 'php011 only reachable source');
    expectSame(9, $solo->getReturn(), 'php011 balance reachable source');
    expectSame(['payments.jsonl'], $opened, 'php011 unused sources unopened');
    expectSame(['payments.jsonl'], $closed, 'php011 single source closed');

    echo "PHP-011 OK\n";
} finally {
    foreach ($paths as $path) {
        if (is_file($path)) unlink($path);
    }
    rmdir($dir);
}
