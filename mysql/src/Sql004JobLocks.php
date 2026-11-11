<?php

declare(strict_types=1);

namespace Practice\MySQL;

use PDO;

/**
 * SQL-004 — InnoDB NOWAIT vs SKIP LOCKED.
 *
 * Table: jobs (InnoDB)
 * - id BIGINT UNSIGNED PRIMARY KEY
 * - payload VARCHAR(100) NOT NULL
 * - status VARCHAR(20) NOT NULL, either 'pending' or 'processing'
 *
 * Seed rows: (1, 'email-A', 'pending'),
 *            (2, 'email-B', 'pending'),
 *            (3, 'email-C', 'pending').
 *
 * All transactions and locking SQL for this exercise belong here.
 */
final class Sql004JobLocks
{
    /**
     * Begin a transaction and acquire an exclusive lock on a job by ID.
     * Keep the transaction OPEN after return so another PDO session can
     * contend with it. Return true if the job exists; otherwise return
     * false and finish your transaction. On error rollback and rethrow.
     *
     * No status changes. This method simulates a worker busy with job 1.
     */
    public function holdJob(PDO $pdo, int $jobId): bool
    {
        throw new \LogicException('SQL-004 holdJob() not implemented');
    }

    /**
     * Release a lock held by holdJob() by ending that transaction.
     * Do not change job status.
     */
    public function releaseJob(PDO $pdo): void
    {
        throw new \LogicException('SQL-004 releaseJob() not implemented');
    }

    /**
     * Try to claim a specific PENDING job immediately.
     * Start your own transaction, lock row with NOWAIT, update status
     * to 'processing', and COMMIT. Return true if claimed; return false
     * if no pending job with that ID exists.
     *
     * If another transaction holds the row lock, NOWAIT must fail
     * IMMEDIATELY: propagate PDOException (MySQL error 3572), after
     * rolling back your transaction. Do not emulate NOWAIT with a timeout.
     */
    public function claimNowait(PDO $pdo, int $jobId): bool
    {
        throw new \LogicException('SQL-004 claimNowait() not implemented');
    }

    /**
     * Claim the pending job with LOWEST ID that is not locked.
     * Start your own transaction, select with SKIP LOCKED, set its status
     * to 'processing', and COMMIT. Return claimed ID, or null when none
     * are available. Clean up transactions on every path.
     *
     * A locked pending job must remain pending for a later retry.
     */
    public function claimNext(PDO $pdo): ?int
    {
        throw new \LogicException('SQL-004 claimNext() not implemented');
    }
}
