<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/CsvOrderSummary.php';

use Practice\CsvOrderSummary;

// Small, readable fixture: only paid orders count.
expectSame(
    ['paid_count' => 3, 'paid_total_cents' => 2000],
    CsvOrderSummary::summarize(__DIR__ . '/../fixtures/orders-small.csv'),
    'PHP-001 small CSV',
);

// A valid file can contain only a header.
expectSame(
    ['paid_count' => 0, 'paid_total_cents' => 0],
    CsvOrderSummary::summarize(__DIR__ . '/../fixtures/orders-header-only.csv'),
    'PHP-001 empty CSV',
);

// Build large input on disk, not in an array. Run under a 16 MB memory limit.
$path = tempnam(sys_get_temp_dir(), 'php001-');
if ($path === false) {
    throw new RuntimeException('Unable to create temporary CSV');
}

try {
    $output = fopen($path, 'wb');
    if ($output === false) {
        throw new RuntimeException('Unable to open temporary CSV');
    }

    try {
        fputcsv($output, ['order_id', 'status', 'amount_cents']);
        for ($i = 0; $i < 1000_000; $i++) {
            fputcsv($output, ['o-' . $i, $i % 2 === 0 ? 'paid' : 'pending', 199]);
        }
    } finally {
        fclose($output);
    }

    expectSame(
        ['paid_count' => 500_000, 'paid_total_cents' => 99_500_000],
        CsvOrderSummary::summarize($path),
        'PHP-001 large CSV',
    );
} finally {
    unlink($path);
}
