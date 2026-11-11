<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Sql003SharedLock.php';

use Practice\MySQL\Sql003SharedLock;

$lock = new Sql003SharedLock();
$a = mysqlLabConnection();
$b = mysqlLabConnection();
$c = mysqlLabConnection();

function sql003EndTransaction(PDO $pdo): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

function sql003ExpectLockTimeout(callable $action, string $label): void
{
    try {
        $action();
    } catch (PDOException $exception) {
        if ((int) ($exception->errorInfo[1] ?? 0) === 1205) {
            return;
        }
        throw new RuntimeException("{$label}: expected MySQL lock timeout (1205), got {$exception->getMessage()}", previous: $exception);
    }
    throw new RuntimeException("{$label}: query did not wait for the incompatible lock");
}

try {
    resetMysqlLab($a);
    $b->exec('SET SESSION innodb_lock_wait_timeout = 1');
    $c->exec('SET SESSION innodb_lock_wait_timeout = 1');

    // Shared + shared: two separate transactions may read and retain
    // compatible shared locks on the same product simultaneously.
    $a->beginTransaction();
    $b->beginTransaction();
    try {
        mysqlLabExpectSame(1, $lock->readAvailable($a, 100), 'SQL-003: A acquires shared lock');
        mysqlLabExpectSame(1, $lock->readAvailable($b, 100), 'SQL-003: B acquires compatible shared lock');

        // An ordinary SELECT still sees the committed version via MVCC.
        mysqlLabExpectSame(
            1,
            (int) $c->query('SELECT available FROM inventory WHERE product_id = 100')->fetchColumn(),
            'SQL-003: ordinary consistent SELECT is not blocked',
        );

        sql003ExpectLockTimeout(
            static fn () => $c->exec('UPDATE inventory SET available = available - 1 WHERE product_id = 100'),
            'SQL-003: UPDATE waits for shared locks',
        );
    } finally {
        sql003EndTransaction($b);
        sql003EndTransaction($a);
    }

    // With the shared locks released, UPDATE is now allowed.
    mysqlLabExpectSame(
        1,
        $c->exec('UPDATE inventory SET available = available - 1 WHERE product_id = 100'),
        'SQL-003: UPDATE succeeds after shared locks released',
    );
    mysqlLabExpectSame(
        0,
        (int) $a->query('SELECT available FROM inventory WHERE product_id = 100')->fetchColumn(),
        'SQL-003: update became visible',
    );

    resetMysqlLab($a);

    // Exclusive + shared: a FOR UPDATE lock conflicts with our shared read.
    $a->beginTransaction();
    try {
        $a->query('SELECT available FROM inventory WHERE product_id = 100 FOR UPDATE')->fetchColumn();
        $b->beginTransaction();
        try {
            sql003ExpectLockTimeout(
                static fn () => $lock->readAvailable($b, 100),
                'SQL-003: shared read waits for exclusive lock',
            );
        } finally {
            sql003EndTransaction($b);
        }
    } finally {
        sql003EndTransaction($a);
    }

    // Missing product must be distinguishable from stock=0.
    $a->beginTransaction();
    try {
        mysqlLabExpectSame(null, $lock->readAvailable($a, 9999), 'SQL-003: missing product returns null');
    } finally {
        sql003EndTransaction($a);
    }

    echo "SQL-003 shared-lock tests passed.\n";
} finally {
    sql003EndTransaction($c);
    sql003EndTransaction($b);
    sql003EndTransaction($a);
    resetMysqlLab($a);
}
