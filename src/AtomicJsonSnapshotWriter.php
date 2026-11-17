<?php

declare(strict_types=1);

namespace Practice;

/**
 * PHP-009 — Atomically replace a JSON snapshot.
 *
 * Scenario: the admin panel periodically writes a small configuration snapshot
 * that other processes may read while the update is happening.
 *
 * Requirements:
 * - Encode with JSON_THROW_ON_ERROR, retaining arrays/scalars/null as JSON.
 * - Never write directly to the destination file.
 * - Write the whole JSON payload to a uniquely named temporary file in
 *   the SAME directory as the destination. Prefix the temp filename
 *   with ".snapshot-" so it is easy to identify and clean up.
 * - Only after a successful complete write, atomically replace the target
 *   with rename() (Linux/POSIX same-filesystem assumption).
 * - On error, throw and remove only the temporary file created by THIS call.
 * - On serialization error, leave the existing destination unchanged.
 * - Create no extra services, dependencies, or background processes.
 *
 * The file content must be valid JSON but its formatting is not specified.
 */
final class AtomicJsonSnapshotWriter
{
    /** @param array<string|int, mixed> $snapshot */
    public function write(string $path, array $snapshot): void
    {
        throw new \LogicException('PHP-009 not implemented');
    }
}
