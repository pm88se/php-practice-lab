<?php

declare(strict_types=1);

namespace Practice;

/**
 * PHP-009 — Entity registry indexed by EXTERNAL STRING IDs.
 *
 * A job worker frequently resolves entities by their external IDs.
 * Reusing a currently alive object is desirable, but the registry must NOT
 * extend its lifetime when the caller discards the object.
 *
 * Your task: implement the contract without retaining strong references.
 *
 * Important design question: why is WeakMap NOT a direct fit here?
 */
final class WeakEntityRegistry
{
    /** @var array<string, \WeakReference> */
    private array $entries = [];

    /**
     * Return the still-alive object registered for $id, or create it.
     * If the previously registered object has been garbage collected,
     * call $factory($id) and register the new object.
     *
     * The factory is called only when there is no live object for $id.
     * Do not cache failed factory calls.
     *
     * @param callable(string): object $factory
     */
    public function resolve(string $id, callable $factory): object
    {
        throw new \LogicException('PHP-009 not implemented');
    }

    /** True only if the object currently registered under $id is alive. */
    public function hasLive(string $id): bool
    {
        throw new \LogicException('PHP-009 not implemented');
    }

    /**
     * Remove expired entries (whose referents are no longer alive).
     * Return the number of entries actually removed.
     * Never remove live entries.
     */
    public function sweep(): int
    {
        throw new \LogicException('PHP-009 not implemented');
    }

    /** Number of key slots, including entries whose referents have expired. */
    public function slotCount(): int
    {
        return count($this->entries);
    }
}
