<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Sql003SharedLock.php';

use Practice\MySQL\Sql003SharedLock;

$lab = new Sql003SharedLock();
$a = mysqlLabConnection();
$b = mysqlLabConnection();
$writer = mysqlLabConnection();

function sql003Cleanup(PDO $pdo): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

function sql003ExpectTimeout(callable $action, string $label): void
{
    try {
        $action();
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1205) {
            return;
        }
        throw new RuntimeException("{$label}: expected InnoDB timeout 1205, got " . $e->getMessage(), previous: $e);
    }
    throw new RuntimeException("{$label}: expected lock timeout, but operation succeeded");
}

try {
    resetMysqlLab($a);
    $writer->exec('SET SESSION innodb_lock_wait_timeout = 1');

    // Two SHARED locks are compatible; the methods MUST open and retain
    // separate transactions themselves.
    mysqlLabExpectSame(1, $lab->beginSharedRead($a, 100), 'SQL-003: reader A reads stock');
    mysqlLabExpectSame(true, $a->inTransaction(), 'SQL-003: A keeps shared lock active');
    mysqlLabExpectSame(1, $lab->beginSharedRead($b, 100), 'SQL-003: reader B reads same row');
    mysqlLabExpectSame(true, $b->inTransaction(), 'SQL-003: B keeps shared lock active');

    // A plain MVCC SELECT is still possible while shared locks are held.
    mysqlLabExpectSame(
        1,
        (int) $writer->query('SELECT available FROM inventory WHERE product_id = 100')->fetchColumn(),
        'SQL-003: nonlocking read is not blocked',
    );

    // A writer must wait for ALL shared-lock holders.
    sql003ExpectTimeout(
        static fn () => $lab->changeAvailable($writer, 100, 0),
        'SQL-003: writer cannot update while shared locks are active',
    );
    mysqlLabExpectSame(false, $writer->inTransaction(), 'SQL-003: failed writer rolled back');

    $lab->finishRead($a);
    mysqlLabExpectSame(false, $a->inTransaction(), 'SQL-003: A released its lock');

    sql003ExpectTimeout(
        static fn () => $lab->changeAvailable($writer, 100, 0),
        'SQL-003: B still retains a shared lock',
    );

    $lab->finishRead($b);
    mysqlLabExpectSame(false, $b->inTransaction(), 'SQL-003: B released its lock');

    $lab->changeAvailable($writer, 100, 0);
    mysqlLabExpectSame(false, $writer->inTransaction(), 'SQL-003: writer committed');
    mysqlLabExpectSame(
        0,
        (int) $a->query('SELECT available FROM inventory WHERE product_id = 100')->fetchColumn(),
        'SQL-003: writer succeeded after both readers finished',
    );

    // A missing product should not leave an active transaction behind.
    mysqlLabExpectSame(null, $lab->beginSharedRead($a, 9999), 'SQL-003: missing product');
    mysqlLabExpectSame(false, $a->inTransaction(), 'SQL-003: missing row leaves no transaction');

    echo "SQL-003 shared lock tests passed.\n";
} finally {
    sql003Cleanup($writer);
    sql003Cleanup($b);
    sql003Cleanup($a);
    resetMysqlLab($a);
}
