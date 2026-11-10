<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Sql001MoneyTransfer.php';

use Practice\MySQL\Sql001MoneyTransfer;

function sql001Balance(PDO $pdo, int $accountId): int
{
    $stmt = $pdo->prepare('SELECT balance_cents FROM accounts WHERE id = ?');
    $stmt->execute([$accountId]);

    $balance = $stmt->fetchColumn();
    if ($balance === false) {
        throw new RuntimeException("Account {$accountId} missing from SQL-001 fixture");
    }
    return (int) $balance;
}

function sql001ExpectException(callable $fn, string $expectedClass, string $label): void
{
    try {
        $fn();
    } catch (Throwable $exception) {
        if (!$exception instanceof $expectedClass) {
            throw new RuntimeException(
                "{$label}: expected {$expectedClass}, got " . $exception::class,
                previous: $exception,
            );
        }
        return;
    }
    throw new RuntimeException("{$label}: expected {$expectedClass}, no exception thrown");
}

$transfer = new Sql001MoneyTransfer();
$pdo = mysqlLabConnection();
$observer = mysqlLabConnection();

try {
    resetMysqlLab($pdo);
    $transfer->transfer($pdo, 1, 2, 2500);
    mysqlLabExpectSame(7500, sql001Balance($observer, 1), 'SQL-001: debit committed');
    mysqlLabExpectSame(7500, sql001Balance($observer, 2), 'SQL-001: credit committed');
    mysqlLabExpectSame(15000, sql001Balance($observer, 1) + sql001Balance($observer, 2), 'SQL-001: total preserved');

    resetMysqlLab($pdo);
    sql001ExpectException(
        static fn () => $transfer->transfer($pdo, 1, 2, 10001),
        RuntimeException::class,
        'SQL-001: insufficient funds',
    );
    mysqlLabExpectSame(10000, sql001Balance($observer, 1), 'SQL-001: failed debit unchanged');
    mysqlLabExpectSame(5000, sql001Balance($observer, 2), 'SQL-001: failed credit unchanged');

    resetMysqlLab($pdo);
    sql001ExpectException(
        static fn () => $transfer->transfer($pdo, 1, 9999, 2000),
        RuntimeException::class,
        'SQL-001: missing receiver after potential debit',
    );
    mysqlLabExpectSame(10000, sql001Balance($observer, 1), 'SQL-001: debit rolled back when receiver absent');
    mysqlLabExpectSame(5000, sql001Balance($observer, 2), 'SQL-001: other balances unchanged');

    resetMysqlLab($pdo);
    sql001ExpectException(
        static fn () => $transfer->transfer($pdo, 1, 2, 0),
        InvalidArgumentException::class,
        'SQL-001: zero amount',
    );
    sql001ExpectException(
        static fn () => $transfer->transfer($pdo, 1, 2, -1),
        InvalidArgumentException::class,
        'SQL-001: negative amount',
    );
    sql001ExpectException(
        static fn () => $transfer->transfer($pdo, 1, 1, 100),
        InvalidArgumentException::class,
        'SQL-001: same account',
    );
    mysqlLabExpectSame(10000, sql001Balance($observer, 1), 'SQL-001: invalid input unchanged');
    mysqlLabExpectSame(5000, sql001Balance($observer, 2), 'SQL-001: invalid input destination unchanged');

    // One more transfer proves the connection was not left in a failed transaction.
    $transfer->transfer($pdo, 2, 3, 500);
    mysqlLabExpectSame(4500, sql001Balance($observer, 2), 'SQL-001: connection usable after failures');
    mysqlLabExpectSame(500, sql001Balance($observer, 3), 'SQL-001: second transfer committed');

    echo "SQL-001 money transfer tests passed.\n";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    resetMysqlLab($pdo);
}
