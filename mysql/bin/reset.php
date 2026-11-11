<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * Reset ONLY the dedicated lab's accounts and inventory fixtures.
 * This is destructive; never point the lab credentials at a production DB.
 */
function resetMysqlLab(PDO $pdo): void
{
    $pdo->exec('DROP TABLE IF EXISTS inventory');
    $pdo->exec('DROP TABLE IF EXISTS accounts');

    $schema = file_get_contents(__DIR__ . '/../schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Could not read mysql/schema.sql');
    }

    $pdo->exec($schema);
    $pdo->exec(
        "INSERT INTO accounts (id, owner, balance_cents)
         VALUES (1, 'Alice', 10000), (2, 'Bob', 5000), (3, 'Carol', 0)"
    );

    $inventorySchema = file_get_contents(__DIR__ . '/../inventory.sql');
    if ($inventorySchema === false) {
        throw new RuntimeException('Could not read mysql/inventory.sql');
    }
    $pdo->exec($inventorySchema);
    $pdo->exec(
        "INSERT INTO inventory (product_id, sku, available)
         VALUES (100, 'LAST-UNIT', 1), (200, 'MULTI-UNIT', 3)"
    );
}

if (isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    resetMysqlLab(mysqlLabConnection());
    echo "MySQL lab fixtures reset.\n";
}
