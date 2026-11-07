<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/JobRunner.php';

use Practice\JobContext;
use Practice\JobRunner;

$context = new JobContext();
$runner = new JobRunner($context);

$firstResult = $runner->run(
    ['id' => 'job-1', 'tenant_id' => 'tenant-A'],
    static function (JobContext $jobContext) use ($context): string {
        expectSame(true, $jobContext === $context, 'PHP-006 injected context identity');
        expectSame('job-1', $jobContext->get('id'), 'PHP-006 seeded job id');
        expectSame('tenant-A', $jobContext->get('tenant_id'), 'PHP-006 seeded tenant');
        $jobContext->set('temporary_token', 'secret-A');
        return 'processed-A';
    },
);
expectSame(
    ['ok' => true, 'value' => 'processed-A', 'error' => null],
    $firstResult,
    'PHP-006 success result',
);
expectSame(null, $context->get('tenant_id'), 'PHP-006 cleanup after success');
expectSame(null, $context->get('temporary_token'), 'PHP-006 cleanup custom state');
expectSame('fallback', $context->get('missing', 'fallback'), 'PHP-006 missing key default');

$failure = new RuntimeException('External API unavailable');
$failedResult = $runner->run(
    ['id' => 'job-2', 'tenant_id' => 'tenant-B'],
    static function (JobContext $jobContext) use ($failure): never {
        expectSame('tenant-B', $jobContext->get('tenant_id'), 'PHP-006 failure sees own tenant');
        expectSame(null, $jobContext->get('temporary_token'), 'PHP-006 failure sees no stale token');
        $jobContext->set('temporary_token', 'secret-B');
        throw $failure;
    },
);
expectSame(false, $failedResult['ok'], 'PHP-006 failure status');
expectSame(null, $failedResult['value'], 'PHP-006 failure value');
expectSame(true, $failedResult['error'] === $failure, 'PHP-006 preserves original exception');
expectSame(null, $context->get('tenant_id'), 'PHP-006 cleanup after exception');
expectSame(null, $context->get('temporary_token'), 'PHP-006 cleanup token after exception');

// PHP Error implements Throwable but does not extend Exception.
$error = new Error('Unexpected runtime error');
$errorResult = $runner->run(
    ['id' => 'job-3'],
    static function (JobContext $jobContext) use ($error): never {
        $jobContext->set('temporary_token', 'secret-C');
        throw $error;
    },
);
expectSame(false, $errorResult['ok'], 'PHP-006 Error failure status');
expectSame(true, $errorResult['error'] === $error, 'PHP-006 captures Error');
expectSame(null, $context->get('temporary_token'), 'PHP-006 cleanup after Error');

// Subsequent job runs in the same worker, with no residual state.
$lastResult = $runner->run(
    ['id' => 'job-4'],
    static function (JobContext $jobContext): array {
        expectSame('job-4', $jobContext->get('id'), 'PHP-006 next job id');
        expectSame(null, $jobContext->get('tenant_id'), 'PHP-006 no tenant leakage');
        expectSame(null, $jobContext->get('temporary_token'), 'PHP-006 no token leakage');
        $jobContext->set('nullable', null);
        expectSame('fallback', $jobContext->get('absent', 'fallback'), 'PHP-006 absent key');
        return ['id' => $jobContext->get('id'), 'nullable' => $jobContext->get('nullable')];
    },
);
expectSame(
    ['ok' => true, 'value' => ['id' => 'job-4', 'nullable' => null], 'error' => null],
    $lastResult,
    'PHP-006 continue after two failures',
);
expectSame(null, $context->get('id'), 'PHP-006 cleared after final job');
