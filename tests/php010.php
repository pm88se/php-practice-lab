<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/NestedPaidEventStream.php';

use Practice\NestedPaidEventStream;

$dir = sys_get_temp_dir() . '/php010-' . bin2hex(random_bytes(6));
if (!mkdir($dir, 0700) && !is_dir($dir)) {
    throw new RuntimeException('php010 cannot make temp folder');
}
$a = $dir . '/a.jsonl';
$b = $dir . '/b.jsonl';
$bad = $dir . '/bad.jsonl';
file_put_contents($a, implode("\n", [
    '{"type":"order.paid","order_id":"o-1","amount_cents":1200}',
    '{"type":"order.created","order_id":"o-2","amount_cents":300}',
    '{"type":"order.paid","order_id":"o-3","amount_cents":700}',
]) . "\n");
file_put_contents($b, implode("\n", [
    '{"type":"order.paid","order_id":"o-4","amount_cents":40}',
    '{"type":"order.cancelled","order_id":"o-5","amount_cents":50}',
]) . "\n");
file_put_contents($bad, "{\"type\":\"order.paid\",\"order_id\":\"good\",\"amount_cents\":1}\nnot valid json\n");

try {
    $opened = [];
    $closed = [];
    $open = static function (string $path) use (&$opened): void { $opened[] = basename($path); };
    $close = static function (string $path) use (&$closed): void { $closed[] = basename($path); };

    $stream = NestedPaidEventStream::stream([$a, $b], $open, $close);
    expectSame([], $opened, 'php010 lazy before iteration');
    expectSame([], $closed, 'php010 not closed before iteration');

    $records = iterator_to_array($stream, false);
    expectSame([
        ['file' => 'a.jsonl', 'order_id' => 'o-1', 'amount_cents' => 1200],
        ['file' => 'a.jsonl', 'order_id' => 'o-3', 'amount_cents' => 700],
        ['file' => 'b.jsonl', 'order_id' => 'o-4', 'amount_cents' => 40],
    ], $records, 'php010 paid events in order');
    expectSame(3, $stream->getReturn(), 'php010 delegated return count');
    expectSame(['a.jsonl', 'b.jsonl'], $opened, 'php010 each opened once');
    expectSame(['a.jsonl', 'b.jsonl'], $closed, 'php010 each closed once');

    $opened = $closed = [];
    $early = NestedPaidEventStream::stream([$a, $b], $open, $close);
    foreach ($early as $row) {
        expectSame('o-1', $row['order_id'], 'php010 early first record');
        break;
    }
    expectSame(['a.jsonl'], $opened, 'php010 early only first file opened');
    expectSame([], $closed, 'php010 suspended generator still owns open file');
    unset($early);
    gc_collect_cycles();
    expectSame(['a.jsonl'], $closed, 'php010 release after generator abandoned');

    $opened = $closed = [];
    $invalid = NestedPaidEventStream::stream([$bad, $b], $open, $close);
    $failed = false;
    try {
        iterator_to_array($invalid, false);
    } catch (JsonException $e) {
        $failed = true;
    }
    expectSame(true, $failed, 'php010 malformed json propagates');
    expectSame(['bad.jsonl'], $opened, 'php010 no subsequent files after parse failure');
    expectSame(['bad.jsonl'], $closed, 'php010 close on parse failure');

    $opened = $closed = [];
    $empty = NestedPaidEventStream::stream([], $open, $close);
    expectSame([], iterator_to_array($empty, false), 'php010 empty list');
    expectSame(0, $empty->getReturn(), 'php010 empty return');
    expectSame([], $opened, 'php010 no opened files for empty list');

    echo "PHP-010 OK\n";
} finally {
    foreach ([$a, $b, $bad] as $path) {
        if (is_file($path)) unlink($path);
    }
    rmdir($dir);
}
