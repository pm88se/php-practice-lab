<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/JobScheduler.php';

use Practice\JobScheduler;

// Higher priority first; jobs with equal priority preserve arrival order.
$scheduler = new JobScheduler();
$scheduler->add('job-1', 'analytics_export', 1);
$scheduler->add('job-2', 'payment_refund', 10);
$scheduler->add('job-3', 'inventory_sync', 5);
$scheduler->add('job-4', 'payment_refund', 10);

expectSame(
    ['id' => 'job-2', 'type' => 'payment_refund', 'priority' => 10],
    $scheduler->next(),
    'PHP-003 highest priority first',
);
expectSame(
    ['id' => 'job-4', 'type' => 'payment_refund', 'priority' => 10],
    $scheduler->next(),
    'PHP-003 equal-priority FIFO',
);
expectSame(
    ['id' => 'job-3', 'type' => 'inventory_sync', 'priority' => 5],
    $scheduler->next(),
    'PHP-003 medium priority',
);
expectSame(
    ['id' => 'job-1', 'type' => 'analytics_export', 'priority' => 1],
    $scheduler->next(),
    'PHP-003 low priority',
);
expectSame(null, $scheduler->next(), 'PHP-003 empty queue');
expectSame(null, $scheduler->next(), 'PHP-003 repeated empty queue');

// Newly arriving jobs may outrank jobs still waiting in the queue.
$scheduler = new JobScheduler();
$scheduler->add('old-low', 'analytics_export', 1);
$scheduler->add('old-high', 'payment_refund', 10);
expectSame(
    ['id' => 'old-high', 'type' => 'payment_refund', 'priority' => 10],
    $scheduler->next(),
    'PHP-003 partial drain',
);
$scheduler->add('new-high', 'payment_refund', 10);
$scheduler->add('new-low', 'inventory_sync', 1);
expectSame(
    ['id' => 'new-high', 'type' => 'payment_refund', 'priority' => 10],
    $scheduler->next(),
    'PHP-003 insertion after partial drain',
);
expectSame(
    ['id' => 'old-low', 'type' => 'analytics_export', 'priority' => 1],
    $scheduler->next(),
    'PHP-003 FIFO across additions',
);
expectSame(
    ['id' => 'new-low', 'type' => 'inventory_sync', 'priority' => 1],
    $scheduler->next(),
    'PHP-003 last job',
);
expectSame(null, $scheduler->next(), 'PHP-003 drained queue');

// Negative, zero, and positive priorities must be ordered numerically.
$scheduler = new JobScheduler();
$scheduler->add('negative', 'low', -2);
$scheduler->add('zero', 'normal', 0);
$scheduler->add('positive', 'high', 2);
expectSame('positive', $scheduler->next()['id'], 'PHP-003 positive priority');
expectSame('zero', $scheduler->next()['id'], 'PHP-003 zero priority');
expectSame('negative', $scheduler->next()['id'], 'PHP-003 negative priority');
