<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/SlowRequestTopK.php';

use Practice\SlowRequestTopK;

$invalid = false;
try {
    new SlowRequestTopK(0);
} catch (InvalidArgumentException $e) {
    $invalid = true;
}
expectSame(true, $invalid, 'php012 capacity must be positive');

$top = new SlowRequestTopK(3);
expectSame(0, $top->count(), 'php012 starts empty');
$top->add('r1', '/orders', 120);
$top->add('r2', '/payments', 900);
$top->add('r3', '/orders', 400);
$top->add('r4', '/health', 40);
expectSame(3, $top->count(), 'php012 bound capacity');
$top->add('r5', '/payments', 600);
expectSame(3, $top->count(), 'php012 retained set stays bounded');
expectSame([
    ['id' => 'r2', 'route' => '/payments', 'duration_ms' => 900],
    ['id' => 'r5', 'route' => '/payments', 'duration_ms' => 600],
    ['id' => 'r3', 'route' => '/orders', 'duration_ms' => 400],
], $top->drain(), 'php012 top-k order');
expectSame(0, $top->count(), 'php012 drained heap');
expectSame([], $top->drain(), 'php012 second drain empty');

// Equal-duration entries favor first arrival, even if a slower entry came later.
$tie = new SlowRequestTopK(2);
$tie->add('first', '/a', 500);
$tie->add('second', '/b', 500);
$tie->add('third', '/c', 500);
$tie->add('tiny', '/d', 1);
expectSame([
    ['id' => 'first', 'route' => '/a', 'duration_ms' => 500],
    ['id' => 'second', 'route' => '/b', 'duration_ms' => 500],
], $tie->drain(), 'php012 stable tie preference');

// Arrival order matters for output too, not just candidate eviction.
$tie = new SlowRequestTopK(4);
$tie->add('a', '/a', 250);
$tie->add('b', '/b', 700);
$tie->add('c', '/c', 700);
$tie->add('d', '/d', 300);
expectSame(['b', 'c', 'd', 'a'], array_column($tie->drain(), 'id'), 'php012 stable final order');

// A slower event can arrive after a partial snapshot/drain.
$top = new SlowRequestTopK(1);
$top->add('old', '/v1', 5);
$top->add('new', '/v2', 12);
expectSame([['id' => 'new', 'route' => '/v2', 'duration_ms' => 12]], $top->drain(), 'php012 capacity one');
$top->add('after-drain', '/v3', 1);
expectSame([['id' => 'after-drain', 'route' => '/v3', 'duration_ms' => 1]], $top->drain(), 'php012 reusable after drain');

$empty = new SlowRequestTopK(5);
expectSame([], $empty->drain(), 'php012 empty drain');

echo "PHP-012 OK\n";
