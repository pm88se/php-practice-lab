<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/WeakEntityRegistry.php';

use Practice\WeakEntityRegistry;

$registry = new WeakEntityRegistry();
$calls = 0;
$factory = static function (string $id) use (&$calls): object {
    ++$calls;
    return (object)['id' => $id, 'instance' => $calls];
};

$first = $registry->resolve('tenant:42', $factory);
expectSame('tenant:42', $first->id, 'php009 first entity');
expectSame(1, $calls, 'php009 factory once');
expectSame(true, $registry->hasLive('tenant:42'), 'php009 alive');

$again = $registry->resolve('tenant:42', $factory);
expectSame(true, $first === $again, 'php009 identity while alive');
expectSame(1, $calls, 'php009 no duplicate factory call');

$other = $registry->resolve('tenant:7', $factory);
expectSame(false, $other === $first, 'php009 different keys');
expectSame(2, $registry->slotCount(), 'php009 two keys');

unset($first, $again);
gc_collect_cycles();
expectSame(false, $registry->hasLive('tenant:42'), 'php009 registry does not own entity');
expectSame(true, $registry->hasLive('tenant:7'), 'php009 unrelated entity still alive');

$replacement = $registry->resolve('tenant:42', $factory);
expectSame(3, $calls, 'php009 recreate expired entity');
expectSame(3, $replacement->instance, 'php009 new instance after collection');

// Cyclic objects are not necessarily collected immediately after unset.
$cyclic = $registry->resolve('tenant:cyclic', static function (string $id): object {
    $node = (object)['id' => $id];
    $node->self = $node;
    return $node;
});
unset($cyclic);
gc_collect_cycles();
expectSame(false, $registry->hasLive('tenant:cyclic'), 'php009 cyclic entity collected');

expectSame(1, $registry->sweep(), 'php009 sweep only expired slot');
expectSame(2, $registry->slotCount(), 'php009 live slots remain');
expectSame(0, $registry->sweep(), 'php009 sweep idempotent');

unset($other, $replacement);
gc_collect_cycles();
expectSame(2, $registry->sweep(), 'php009 clean remaining entries');
expectSame(0, $registry->slotCount(), 'php009 empty');

// Failed factory calls must not create entries.
$failures = new WeakEntityRegistry();
$thrown = false;
try {
    $failures->resolve('broken', static function (string $id): object {
        throw new RuntimeException('lookup failed');
    });
} catch (RuntimeException $e) {
    $thrown = true;
}
expectSame(true, $thrown, 'php009 propagates factory error');
expectSame(0, $failures->slotCount(), 'php009 does not cache failed factory');

echo "PHP-009 OK\n";
