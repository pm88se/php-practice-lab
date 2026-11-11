<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Sql003IsolationObserver.php';

use Practice\MySQL\Sql003IsolationObserver;

$observer = new Sql003IsolationObserver();
$reader = mysqlLabConnection();
$writer = mysqlLabConnection();

try {
    foreach ([
        'READ COMMITTED' => [10000, 9000],
        'REPEATABLE READ' => [10000, 10000],
    ] as $isolation => $expected) {
        resetMysqlLab($writer);
        $calls = 0;
        $result = $observer->observe(
            $reader,
            1,
            $isolation,
            static function () use ($reader, $writer, &$calls): void {
                ++$calls;

                // A consistent read must not block an independent UPDATE.
                if (!$reader->inTransaction()) {
                    throw new RuntimeException('SQL-003: callback must execute inside reader transaction');
                }

                $statement = $writer->prepare(
                    'UPDATE accounts SET balance_cents = :new_balance WHERE id = :account_id'
                );
                $statement->execute(['new_balance' => 9000, 'account_id' => 1]);
                mysqlLabExpectSame(1, $statement->rowCount(), 'SQL-003: writer changed a row');
            },
        );

        mysqlLabExpectSame($expected, $result, "SQL-003: {$isolation} read visibility");
        mysqlLabExpectSame(1, $calls, "SQL-003: {$isolation} callback called exactly once");
        mysqlLabExpectSame(false, $reader->inTransaction(), "SQL-003: {$isolation} reader transaction closed");
        mysqlLabExpectSame(
            9000,
            (int) $writer->query('SELECT balance_cents FROM accounts WHERE id = 1')->fetchColumn(),
            "SQL-003: {$isolation} writer committed balance",
        );
    }

    // Do not interpolate arbitrary isolation levels into SQL.
    $called = false;
    try {
        $observer->observe($reader, 1, 'UNSAFE LEVEL', static function () use (&$called): void {
            $called = true;
        });
        throw new RuntimeException('SQL-003: invalid isolation was accepted');
    } catch (InvalidArgumentException) {
        mysqlLabExpectSame(false, $called, 'SQL-003: unsupported isolation callback not called');
        mysqlLabExpectSame(false, $reader->inTransaction(), 'SQL-003: unsupported isolation left no transaction');
    }

    echo "SQL-003 isolation visibility tests passed.\n";
} finally {
    if ($reader->inTransaction()) {
        $reader->rollBack();
    }
    if ($writer->inTransaction()) {
        $writer->rollBack();
    }
    resetMysqlLab($writer);
}
