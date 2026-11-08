<?php

declare(strict_types=1);

namespace Practice;

/**
 * Mutable state scoped to one job in a long-running CLI worker.
 */
final class JobContext
{
    private array $state;
    public function __construct()
    {
        $this->state = [];
    }
    /** Store one value for the currently running job. */
    public function set(string $key, mixed $value): void
    {
        $this->state[$key] = $value;
    }

    /** Return stored value, or $default when the key is absent. */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->state)) {
            return $this->state[$key];
        }
        return  $default;
    }

    /** Remove all state before the next job starts. */
    public function clear(): void
    {
        $this->state = [];
    }
}
