<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Environment.php';

use Practice\Environment;

function expectSame(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf('%s: expected %s, got %s', $label, var_export($expected, true), var_export($actual, true)));
    }
}

expectSame('php-practice-lab', Environment::name(), 'environment name');

require_once __DIR__ . '/php001.php';
require_once __DIR__ . '/php002.php';
require_once __DIR__ . '/php003.php';
require_once __DIR__ . '/php004.php';
require_once __DIR__ . '/php005.php';
require_once __DIR__ . '/php006.php';

echo "Tests passed.\n";
