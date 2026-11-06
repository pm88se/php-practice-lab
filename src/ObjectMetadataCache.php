<?php

declare(strict_types=1);

namespace Practice;

/**
 * Cache derived metadata per object without extending the object's lifetime.
 */
final class ObjectMetadataCache
{
    /**
     * Return cached metadata for an object, or compute it exactly once on a miss.
     * Null is a valid cached value and must not trigger recomputation.
     *
     * @param callable(object): mixed $compute
     */
    public function get(object $object, callable $compute): mixed
    {
        throw new \LogicException('PHP-004 get() not implemented yet');
    }

    public function has(object $object): bool
    {
        throw new \LogicException('PHP-004 has() not implemented yet');
    }

    public function forget(object $object): void
    {
        throw new \LogicException('PHP-004 forget() not implemented yet');
    }

    public function count(): int
    {
        throw new \LogicException('PHP-004 count() not implemented yet');
    }
}
