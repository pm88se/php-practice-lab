<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bin/reset.php';

function mysqlLabExpectSame(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            '%s: expected %s, got %s',
            $label,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

// The same test runner works locally and in GitHub Actions.
// Every run resets ONLY the dedicated accounts fixture to a known state.
$sessionA = mysqlLabConnection();
$sessionB = mysqlLabConnection();

$engine = $sessionA->query(
    "SELECT ENGINE FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounts'"
);
resetMysqlLab($sessionA);
$engine = $sessionA->query(
    "SELECT ENGINE FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accounts'"
)->fetchColumn();
mysqlLabExpectSame('InnoDB', $engine, 'MySQL uses InnoDB');

mysqlLabExpectSame(
    10000,
    (int) $sessionA->query('SELECT balance_cents FROM accounts WHERE id = 1')->fetchColumn(),
    'seeded account 1 balance',
);
mysqlLabExpectSame(
    5000,
    (int) $sessionB->query('SELECT balance_cents FROM accounts WHERE id = 2')->fetchColumn(),
    'second connection can read fixture',
);

// Verify actual transaction rollback: not an in-memory PHP simulation.
$sessionA->beginTransaction();
try {
    $sessionA->exec('UPDATE accounts SET balance_cents = 9000 WHERE id = 1');
} finally {
    $sessionA->rollBack();
}

mysqlLabExpectSame(
    10000,
    (int) $sessionB->query('SELECT balance_cents FROM accounts WHERE id = 1')->fetchColumn(),
    'rollback restores original balance',
);

$sessionA->beginTransaction();
$sessionA->exec('UPDATE accounts SET balance_cents = 9900 WHERE id = 1');
$sessionA->commit();
mysqlLabExpectSame(
    9900,
    (int) $sessionB->query('SELECT balance_cents FROM accounts WHERE id = 1')->fetchColumn(),
    'committed change visible across connections',
);

// Leave the lab ready for manual exercises.
resetMysqlLab($sessionA);

echo "MySQL lab smoke tests passed.\n";
