<?php

declare(strict_types=1);

namespace Practice;

require_once __DIR__ . '/JobContext.php';

/**
 * Run one job with isolated context; report failures without killing the worker.
 */
final class JobRunner
{
    public function __construct(private readonly JobContext $context)
    {
    }

    /**
     * Each string-keyed entry in $job becomes a key/value in JobContext.
     * Handler receives the same context instance injected in the constructor.
     * A failure result contains the original Throwable; exceptions must not be swallowed.
     *
     * @param array<string, mixed> $job
     * @param callable(JobContext): mixed $handler
     * @return array{ok: bool, value: mixed, error: ?\Throwable}
     */
    public function run(array $job, callable $handler): array
    {
        throw new \LogicException('PHP-006 JobRunner::run() not implemented yet');
    }
}
