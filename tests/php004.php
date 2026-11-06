<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/ObjectMetadataCache.php';

use Practice\ObjectMetadataCache;

$cache = new ObjectMetadataCache();
$first = (object) ['id' => 10];
$second = (object) ['id' => 10];
$computations = 0;
$compute = static function (object $object) use (&$computations): string {
    $computations++;
    return 'meta-' . $object->id;
};

// Same object: compute once, reuse cached metadata.
expectSame('meta-10', $cache->get($first, $compute), 'PHP-004 first cache miss');
expectSame('meta-10', $cache->get($first, $compute), 'PHP-004 cache hit');
expectSame(1, $computations, 'PHP-004 computed once');
expectSame(true, $cache->has($first), 'PHP-004 has cached object');

// Different object, even with identical properties, has its own cache entry.
expectSame('meta-10', $cache->get($second, $compute), 'PHP-004 distinct identity');
expectSame(2, $computations, 'PHP-004 separate computation for distinct object');
expectSame(2, $cache->count(), 'PHP-004 count two live entries');

// A null result must still be cached.
$nullObject = new stdClass();
$nullComputations = 0;
$computeNull = static function (object $object) use (&$nullComputations): mixed {
    $nullComputations++;
    return null;
};
expectSame(null, $cache->get($nullObject, $computeNull), 'PHP-004 cache null');
expectSame(null, $cache->get($nullObject, $computeNull), 'PHP-004 reuse cached null');
expectSame(1, $nullComputations, 'PHP-004 compute null only once');
expectSame(true, $cache->has($nullObject), 'PHP-004 has cached null');
expectSame(3, $cache->count(), 'PHP-004 null adds an entry');

// Explicit invalidation makes the next get recompute.
$cache->forget($first);
expectSame(false, $cache->has($first), 'PHP-004 forget');
expectSame(2, $cache->count(), 'PHP-004 count after forget');
expectSame('meta-10', $cache->get($first, $compute), 'PHP-004 recompute after forget');
expectSame(3, $computations, 'PHP-004 recomputation count');
$cache->forget(new stdClass()); // Forgetting an absent key should be safe.
expectSame(3, $cache->count(), 'PHP-004 forget absent object is no-op');

// Weak ownership: once the last external strong reference is released,
// the entry must disappear and the object must be collectible.
$temporary = new stdClass();
$weak = WeakReference::create($temporary);
expectSame('ephemeral', $cache->get($temporary, static fn (object $object): string => 'ephemeral'), 'PHP-004 temporary entry');
expectSame(4, $cache->count(), 'PHP-004 count with temporary object');
unset($temporary);
gc_collect_cycles();
expectSame(null, $weak->get(), 'PHP-004 cache does not retain object');
expectSame(3, $cache->count(), 'PHP-004 entry removed after collection');

// The cache itself remains alive, so this explicitly tests key ownership.
unset($weak, $first, $second, $nullObject, $compute, $computeNull);
gc_collect_cycles();
expectSame(0, $cache->count(), 'PHP-004 collected objects no longer cached');
