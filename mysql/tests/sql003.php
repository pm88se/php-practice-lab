<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Sql003PaymentWebhook.php';

use Practice\MySQL\Sql003PaymentWebhook;

function sql003Balance(PDO $pdo, int $accountId): int
{
    $stmt = $pdo->prepare('SELECT balance_cents FROM accounts WHERE id = ?');
    $stmt->execute([$accountId]);
    return (int) $stmt->fetchColumn();
}

function sql003EventCount(PDO $pdo, string $eventId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM payment_events WHERE event_id = ?');
    $stmt->execute([$eventId]);
    return (int) $stmt->fetchColumn();
}

function sql003ExpectException(callable $action, string $expected, string $label): void
{
    try {
        $action();
    } catch (Throwable $exception) {
        if (!$exception instanceof $expected) {
            throw new RuntimeException("{$label}: expected {$expected}, got " . $exception::class, previous: $exception);
        }
        return;
    }
    throw new RuntimeException("{$label}: expected {$expected}, no exception thrown");
}

$webhook = new Sql003PaymentWebhook();
$sessionA = mysqlLabConnection();
$sessionB = mysqlLabConnection();

try {
    resetMysqlLab($sessionA);

    mysqlLabExpectSame(
        true,
        $webhook->apply($sessionA, 'payment-001', 1, 2500),
        'SQL-003: first delivery applied',
    );
    mysqlLabExpectSame(12500, sql003Balance($sessionB, 1), 'SQL-003: first credit committed');
    mysqlLabExpectSame(1, sql003EventCount($sessionB, 'payment-001'), 'SQL-003: first event recorded');

    // Provider retry: same event ID, even with inconsistent retry payload.
    mysqlLabExpectSame(
        false,
        $webhook->apply($sessionB, 'payment-001', 1, 9000),
        'SQL-003: duplicate delivery ignored',
    );
    mysqlLabExpectSame(12500, sql003Balance($sessionA, 1), 'SQL-003: duplicate did not credit twice');
    mysqlLabExpectSame(1, sql003EventCount($sessionA, 'payment-001'), 'SQL-003: duplicate has one event row');

    // A distinct event is a legitimate additional payment.
    mysqlLabExpectSame(true, $webhook->apply($sessionB, 'payment-002', 1, 500), 'SQL-003: new event applied');
    mysqlLabExpectSame(13000, sql003Balance($sessionA, 1), 'SQL-003: new event credited');

    // Event and balance must commit together; nonexistent account cannot consume event ID.
    sql003ExpectException(
        static fn () => $webhook->apply($sessionA, 'payment-failed', 9999, 200),
        RuntimeException::class,
        'SQL-003: missing account rejected',
    );
    mysqlLabExpectSame(0, sql003EventCount($sessionB, 'payment-failed'), 'SQL-003: failed event rolled back');
    mysqlLabExpectSame(13000, sql003Balance($sessionB, 1), 'SQL-003: failed event leaves balance alone');

    // Once the failed event has been rolled back, its ID may be used for a valid account.
    mysqlLabExpectSame(true, $webhook->apply($sessionB, 'payment-failed', 2, 200), 'SQL-003: failed ID can be retried');
    mysqlLabExpectSame(5200, sql003Balance($sessionA, 2), 'SQL-003: retried event credited');

    sql003ExpectException(
        static fn () => $webhook->apply($sessionA, '', 1, 100),
        InvalidArgumentException::class,
        'SQL-003: empty event ID rejected',
    );
    sql003ExpectException(
        static fn () => $webhook->apply($sessionA, 'payment-zero', 1, 0),
        InvalidArgumentException::class,
        'SQL-003: zero amount rejected',
    );
    mysqlLabExpectSame(0, sql003EventCount($sessionB, 'payment-zero'), 'SQL-003: invalid event not inserted');
    mysqlLabExpectSame(false, $sessionA->inTransaction(), 'SQL-003: connection A left clean');
    mysqlLabExpectSame(false, $sessionB->inTransaction(), 'SQL-003: connection B left clean');

    echo "SQL-003 payment webhook tests passed.\n";
} finally {
    if ($sessionA->inTransaction()) {
        $sessionA->rollBack();
    }
    if ($sessionB->inTransaction()) {
        $sessionB->rollBack();
    }
    resetMysqlLab($sessionA);
}
