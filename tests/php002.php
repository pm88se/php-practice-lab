<?php

declare(strict_types=1);

use Practice\CsvOrderSummary;

$small = __DIR__ . '/../fixtures/orders-small.csv';
$paid = CsvOrderSummary::paidOrders($small);

expectSame(true, $paid instanceof Generator, 'PHP-002 returns a Generator');

expectSame(
    [
        ['order_id' => 'o-1', 'amount_cents' => 1299],
        ['order_id' => 'o-3', 'amount_cents' => 701],
        ['order_id' => 'o-5', 'amount_cents' => 0],
    ],
    iterator_to_array($paid, false), // Small fixture only; never materialize the large CSV.
    'PHP-002 paid-order feed',
);

expectSame(
    [
        ['order_id' => 'o-1', 'amount_cents' => 1299],
        ['order_id' => 'o-3', 'amount_cents' => 701],
    ],
    CsvOrderSummary::previewPaid($small, 2),
    'PHP-002 first two paid orders',
);

expectSame([], CsvOrderSummary::previewPaid($small, 0), 'PHP-002 zero preview limit');
expectSame([], CsvOrderSummary::previewPaid($small, -1), 'PHP-002 negative preview limit');
expectSame([], CsvOrderSummary::previewPaid(__DIR__ . '/../fixtures/orders-header-only.csv', 5), 'PHP-002 empty preview');
