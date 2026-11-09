<?php

declare(strict_types=1);

namespace Practice;

/**
 * Persistable progress of a long-running import operation.
 *
 * A live stream is runtime-only state and must never be serialized or
 * shared between cloned checkpoint objects.
 */
final class ImportCheckpoint
{
    private int $processed = 0;

    /** @var resource|null */
    private mixed $stream = null;

    public function __construct(public readonly string $importId, int $processed = 0)
    {
        $this->processed = $processed;
    }

    public function processed(): int
    {
        return $this->processed;
    }

    public function advance(int $count): void
    {
        $this->processed += $count;
    }

    /**
     * Attach an already-open stream for the active PHP process.
     *
     * @param resource $stream
     */
    public function attachStream(mixed $stream): void
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Expected an open stream resource');
        }

        $this->stream = $stream;
    }

    public function hasStream(): bool
    {
        return is_resource($this->stream);
    }

    /**
     * Only persist durable state. Expected keys: importId, processed.
     *
     * @return array{importId: string, processed: int}
     */
    public function __serialize(): array
    {
        throw new \LogicException('PHP-007 __serialize() not implemented yet');
    }

    /**
     * Restore durable state and reset transient runtime state.
     * The constructor is not automatically called by unserialize().
     *
     * @param array{importId: string, processed: int} $data
     */
    public function __unserialize(array $data): void
    {
        throw new \LogicException('PHP-007 __unserialize() not implemented yet');
    }

    /** A cloned checkpoint must not share the original stream handle. */
    public function __clone(): void
    {
        throw new \LogicException('PHP-007 __clone() not implemented yet');
    }
}
