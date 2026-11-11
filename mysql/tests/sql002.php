<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Sql002InventoryReservation.php';

use Practice\MySQL\Sql002InventoryReservation;

function sql002Available(PDO $pdo, int $productId): int
{
    $query = $pdo->prepare('SELECT available FROM inventory WHERE product_id = ?');
    $query->execute([$productId]);
    $available = $query->fetchColumn();

    if ($available === false) {
        throw new RuntimeException('SQL-002 fixture product missing');
    }

    return (int) $available;
}

$reserver = new Sql002InventoryReservation();
$first = mysqlLabConnection();
$second = mysqlLabConnection();

try {
    resetMysqlLab($first);

    // Single available unit can be reserved exactly once.
    mysqlLabExpectSame(true, $reserver->reserve($first, 100), 'SQL-002: first reservation succeeds');
    mysqlLabExpectSame(0, sql002Available($second, 100), 'SQL-002: stock decremented and committed');
    mysqlLabExpectSame(false, $reserver->reserve($second, 100), 'SQL-002: second reservation refused');
    mysqlLabExpectSame(0, sql002Available($first, 100), 'SQL-002: no overselling');

    // No row: reserve() must not claim success.
    mysqlLabExpectSame(false, $reserver->reserve($first, 9999), 'SQL-002: unknown product refused');

    // Real lock competition between independent sessions.
    // The first session owns an exclusive row lock until it commits.
    resetMysqlLab($first);
    $first->beginTransaction();
    $first->query('SELECT available FROM inventory WHERE product_id = 100 FOR UPDATE')->fetchColumn();

    try {
        // Keep intentional lock waits short so the exercise is quick to run.
        $second->exec('SET SESSION innodb_lock_wait_timeout = 1');

        $timedOut = false;
        try {
            $reserver->reserve($second, 100);
        } catch (PDOException $exception) {
            $timedOut = ($exception->errorInfo[1] ?? null) === 1205;
            if (!$timedOut) {
                throw $exception;
            }
        }

        mysqlLabExpectSame(true, $timedOut, 'SQL-002: concurrent reservation must wait on locked row');
        mysqlLabExpectSame(false, $second->inTransaction(), 'SQL-002: timed-out transaction rolled back');
    } finally {
        if ($second->inTransaction()) {
            $second->rollBack();
        }
        if ($first->inTransaction()) {
            $first->rollBack();
        }
    }

    // Stock remains available after the first session releases its lock.
    mysqlLabExpectSame(1, sql002Available($first, 100), 'SQL-002: rollback preserved last unit');
    mysqlLabExpectSame(true, $reserver->reserve($second, 100), 'SQL-002: retry after lock release succeeds');
    mysqlLabExpectSame(0, sql002Available($first, 100), 'SQL-002: last unit reserved once');

    echo "SQL-002 inventory reservation tests passed.\n";
} finally {
    if ($first->inTransaction()) {
        $first->rollBack();
    }
    if ($second->inTransaction()) {
        $second->rollBack();
    }
    resetMysqlLab($first);
}
