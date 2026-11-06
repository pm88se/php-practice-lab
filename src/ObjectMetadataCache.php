<?php

declare(strict_types=1);

namespace Practice;

use WeakMap;

/**
 * Cache derived metadata per object without extending the object's lifetime.
 */
final class ObjectMetadataCache
{
    private WeakMap $cache;
    public function __construct()
    {
        $this->cache = new WeakMap;
    }
    /**
     * Return cached metadata for an object, or compute it exactly once on a miss.
     * Null is a valid cached value and must not trigger recomputation.
     *
     * @param callable(object): mixed $compute
     */
    public function get(object $object, callable $compute): mixed
    {
        if ($this->has($object)) {
            return $this->cache[$object]['value'];
        }
        $computed = $compute($object);
        $this->cache[$object] = ['value' => $computed];
        return $this->cache[$object]['value'];
    }

    public function has(object $object): bool
    {
        return isset($this->cache[$object]);
    }

    public function forget(object $object): void
    {
        unset($this->cache[$object]);
    }

    public function count(): int
    {
        return count($this->cache);
    }
}
