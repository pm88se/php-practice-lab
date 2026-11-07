<?php

declare(strict_types=1);

namespace Practice;

/**
 * Mutable state scoped to one job in a long-running CLI worker.
 */
final class JobContext
{
    /** Store one value for the currently running job. */
    public function set(string $key, mixed $value): void
    {
        throw new \LogicException('PHP-006 JobContext::set() not implemented yet');
    }

    /** Return stored value, or $default when the key is absent. */
    public function get(string $key, mixed $default = null): mixed
    {
        throw new \LogicException('PHP-006 JobContext::get() not implemented yet');
    }

    /** Remove all state before the next job starts. */
    public function clear(): void
    {
        throw new \LogicException('PHP-006 JobContext::clear() not implemented yet');
    }
}
