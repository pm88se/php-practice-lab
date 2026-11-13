<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

/**
 * PERF-002 fixture: creates three order items for every existing perf_order.
 * Requires first running mysql/performance/bin/seed.php medium (or small/large).
 *
 * Does not modify perf_orders, existing indexes, or other practice fixtures.
 * TRUNCATE clears ONLY perf_order_items. Use disposable MySQL lab credentials.
 */
$pdo = mysqlLabConnection();
$schema = file_get_contents(__DIR__ . '/../order_items.sql');
if ($schema === false) {
    throw new RuntimeException('Cannot read mysql/performance/order_items.sql');
}
$pdo->exec($schema);

$orders = (int) $pdo->query('SELECT COUNT(*) FROM perf_orders')->fetchColumn();
if ($orders === 0) {
    throw new RuntimeException('perf_orders is empty: run php mysql/performance/bin/seed.php medium first');
}

$pdo->exec('TRUNCATE TABLE perf_order_items');
$start = microtime(true);

for ($startId = 1; $startId <= $orders; $startId += 100) {
    $endId = min($orders, $startId + 99);
    $values = [];
    $params = [];

    for ($orderId = $startId; $orderId <= $endId; ++$orderId) {
        // Three items per order. The relationship is deterministic.
        // Different unit prices and quantities make the aggregation non-trivial.
        for ($line = 1; $line <= 3; ++$line) {
            $hash = ($orderId * 48271 + $line * 7927) % 2147483647;
            $values[] = '(?, ?, ?, ?)';
            array_push(
                $params,
                $orderId,
                1 + ($hash % 10_000),
                1 + ($hash % 4),
                100 + ($hash % 50_000),
            );
        }
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO perf_order_items (order_id, product_id, quantity, unit_price_cents) VALUES '
            . implode(', ', $values)
        );
        $stmt->execute($params);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }

    if ($endId % 50_000 === 0 || $endId === $orders) {
        printf("Generated order items for %d / %d orders\n", $endId, $orders);
    }
}

$pdo->query('ANALYZE TABLE perf_order_items')->fetchAll();
printf("Done: %d orders, %d items, %.1f seconds\n", $orders, $orders * 3, microtime(true) - $start);
