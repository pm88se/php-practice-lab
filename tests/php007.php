<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TaskDependencyPlanner.php';

use Practice\TaskDependencyPlanner;

$planner = new TaskDependencyPlanner();

expectSame([], $planner->plan([]), 'PHP-007 empty graph');
expectSame(['single'], $planner->plan(['single' => []]), 'PHP-007 single task');

// The order of independent tasks is stable: seed the ready queue using input order.
expectSame(
    ['lint', 'build', 'docs'],
    $planner->plan(['lint' => [], 'build' => [], 'docs' => []]),
    'PHP-007 independent tasks preserve input order',
);

// The input deliberately lists dependent tasks before their prerequisites.
expectSame(
    ['build', 'test', 'deploy'],
    $planner->plan([
        'deploy' => ['build', 'test'],
        'test' => ['build'],
        'build' => [],
    ]),
    'PHP-007 prerequisites precede dependent tasks',
);

// When multiple tasks are initially ready, they remain ahead of tasks that
// become ready only later. The queue also preserves dependent discovery order.
expectSame(
    ['lint', 'build', 'test', 'docs', 'package', 'deploy'],
    $planner->plan([
        'lint' => [],
        'build' => [],
        'package' => ['lint', 'test', 'docs'],
        'test' => ['build'],
        'docs' => ['build'],
        'deploy' => ['package'],
    ]),
    'PHP-007 deterministic FIFO ready queue',
);

// Repeated references to the same prerequisite are only one dependency edge.
expectSame(
    ['build', 'test', 'deploy'],
    $planner->plan([
        'deploy' => ['test', 'test'],
        'test' => ['build', 'build'],
        'build' => [],
    ]),
    'PHP-007 duplicate prerequisites do not block tasks',
);

$unknownDependency = false;
try {
    $planner->plan(['deploy' => ['missing'], 'build' => []]);
} catch (InvalidArgumentException) {
    $unknownDependency = true;
}
expectSame(true, $unknownDependency, 'PHP-007 rejects undeclared prerequisites');

$cycleDetected = false;
try {
    $planner->plan(['a' => ['b'], 'b' => ['c'], 'c' => ['a']]);
} catch (LogicException) {
    $cycleDetected = true;
}
expectSame(true, $cycleDetected, 'PHP-007 rejects cycles');

$selfCycle = false;
try {
    $planner->plan(['a' => ['a']]);
} catch (LogicException) {
    $selfCycle = true;
}
expectSame(true, $selfCycle, 'PHP-007 rejects self-dependency');

// An unrelated valid component must not hide a cycle elsewhere.
$partialCycle = false;
try {
    $planner->plan(['ready' => [], 'done' => ['ready'], 'x' => ['y'], 'y' => ['x']]);
} catch (LogicException) {
    $partialCycle = true;
}
expectSame(true, $partialCycle, 'PHP-007 detects cycles in disconnected components');

// Reuse the same planner after invalid graphs: no previous plan should leak.
expectSame(
    ['load', 'transform', 'publish'],
    $planner->plan([
        'publish' => ['transform'],
        'transform' => ['load'],
        'load' => [],
    ]),
    'PHP-007 planner reusable after errors',
);

// Exercise longer dependency chains with an iterative approach.
$chain = [];
for ($i = 199; $i >= 0; $i--) {
    $chain['step-' . $i] = $i === 0 ? [] : ['step-' . ($i - 1)];
}
$expected = [];
for ($i = 0; $i < 200; $i++) {
    $expected[] = 'step-' . $i;
}
expectSame($expected, $planner->plan($chain), 'PHP-007 long chain');
