<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$profiles = [
    'small' => 20_000,
    'medium' => 200_000,
    'large' => 1_000_000,
];

$profile = $argv[1] ?? '';
if (!isset($profiles[$profile])) {
    fwrite(STDERR, "Usage: php mysql/performance/bin/seed.php small|medium|large\n");
    exit(1);
}

$pdo = mysqlLabConnection();
$schema = file_get_contents(__DIR__ . '/../schema.sql');
if ($schema === false) {
    throw new RuntimeException('Could not read mysql/performance/schema.sql');
}

$pdo->exec($schema);
$pdo->exec('TRUNCATE TABLE perf_orders');

$count = $profiles[$profile];
$batchSize = 250;
$start = microtime(true);

for ($offset = 0; $offset < $count; $offset += $batchSize) {
    $rows = min($batchSize, $count - $offset);
    $values = [];
    $params = [];

    for ($index = 0; $index < $rows; ++$index) {
        $id = $offset + $index + 1;

        // Deterministic, intentionally uneven tenant distribution.
        $hash = ($id * 48271) % 2147483647;
        $tenantId = $id % 5 < 2 ? 1 : 2 + ($hash % 99);
        $status = match ($hash % 20) {
            0, 1, 2 => 'cancelled',
            3, 4, 5, 6, 7 => 'pending',
            default => 'paid',
        };

        // Timestamp is intentionally not monotonically correlated with id.
        $timestamp = 1735689600 + (($hash * 97) % 450) * 86400 + ($hash % 86400);
        $values[] = '(?, ?, ?, ?, ?)';
        array_push(
            $params,
            $tenantId,
            1 + ($hash % 50_000),
            $status,
            gmdate('Y-m-d H:i:s', $timestamp),
            100 + ($hash % 500_000),
        );
    }

    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            'INSERT INTO perf_orders (tenant_id, customer_id, status, created_at, total_cents) VALUES '
            . implode(', ', $values)
        );
        $statement->execute($params);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }

    if (($offset + $rows) % 50_000 === 0 || $offset + $rows === $count) {
        printf("Seeded %d / %d rows\n", $offset + $rows, $count);
    }
}

$pdo->exec('ANALYZE TABLE perf_orders');
printf("Done: %s profile (%d rows), %.1f seconds.\n", $profile, $count, microtime(true) - $start);
