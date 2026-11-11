<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Sql004JobLocks.php';

use Practice\MySQL\Sql004JobLocks;

function sql004JobStatus(PDO $pdo, int $id): string
{
    $statement = $pdo->prepare('SELECT status FROM jobs WHERE id = ?');
    $statement->execute([$id]);
    $status = $statement->fetchColumn();
    if ($status === false) {
        throw new RuntimeException("SQL-004: job {$id} missing");
    }
    return (string) $status;
}

function sql004AssertNowaitFailure(callable $action): void
{
    try {
        $action();
    } catch (PDOException $exception) {
        $code = (int) ($exception->errorInfo[1] ?? 0);
        mysqlLabExpectSame(3572, $code, 'SQL-004: NOWAIT produces lock error 3572');
        return;
    }

    throw new RuntimeException('SQL-004: NOWAIT unexpectedly succeeded on a locked job');
}

$lab = new Sql004JobLocks();
$a = mysqlLabConnection();
$b = mysqlLabConnection();
$check = mysqlLabConnection();

try {
    resetMysqlLab($check);

    // All locking and transaction code belongs to the implementation.
    mysqlLabExpectSame(true, $lab->holdJob($a, 1), 'SQL-004: worker A holds job 1');
    mysqlLabExpectSame(true, $a->inTransaction(), 'SQL-004: worker A left transaction open');

    // NOWAIT fails immediately rather than waiting on worker A.
    sql004AssertNowaitFailure(static fn () => $lab->claimNowait($b, 1));
    mysqlLabExpectSame(false, $b->inTransaction(), 'SQL-004: failed NOWAIT rolled back');
    mysqlLabExpectSame('pending', sql004JobStatus($check, 1), 'SQL-004: NOWAIT did not modify locked job');

    // SKIP LOCKED lets B take job 2, even though lower ID job 1 is locked.
    mysqlLabExpectSame(2, $lab->claimNext($b), 'SQL-004: SKIP LOCKED chooses job 2');
    mysqlLabExpectSame('processing', sql004JobStatus($check, 2), 'SQL-004: job 2 claimed');
    mysqlLabExpectSame('pending', sql004JobStatus($check, 1), 'SQL-004: locked job 1 still pending');

    mysqlLabExpectSame(3, $lab->claimNext($b), 'SQL-004: next unlocked job is 3');
    mysqlLabExpectSame(null, $lab->claimNext($b), 'SQL-004: remaining pending job is locked');
    mysqlLabExpectSame(false, $b->inTransaction(), 'SQL-004: claimant always ends transaction');

    $lab->releaseJob($a);
    mysqlLabExpectSame(false, $a->inTransaction(), 'SQL-004: worker A released row lock');
    mysqlLabExpectSame(1, $lab->claimNext($b), 'SQL-004: released job 1 can be claimed');
    mysqlLabExpectSame('processing', sql004JobStatus($check, 1), 'SQL-004: job 1 claimed once released');
    mysqlLabExpectSame(null, $lab->claimNext($b), 'SQL-004: no pending jobs left');

    // NOWAIT should also succeed on an available row, and skip already-claimed rows.
    resetMysqlLab($check);
    mysqlLabExpectSame(true, $lab->claimNowait($b, 3), 'SQL-004: NOWAIT succeeds on unlocked job');
    mysqlLabExpectSame(false, $lab->claimNowait($b, 3), 'SQL-004: already processing job cannot be reclaimed');
    mysqlLabExpectSame('processing', sql004JobStatus($check, 3), 'SQL-004: NOWAIT updated status');

    mysqlLabExpectSame(false, $lab->holdJob($a, 9999), 'SQL-004: missing hold target');
    mysqlLabExpectSame(false, $a->inTransaction(), 'SQL-004: missing hold target closes transaction');

    echo "SQL-004 NOWAIT/SKIP LOCKED tests passed.\n";
} finally {
    if ($a->inTransaction()) {
        $a->rollBack();
    }
    if ($b->inTransaction()) {
        $b->rollBack();
    }
    if ($check->inTransaction()) {
        $check->rollBack();
    }
    resetMysqlLab($check);
}
