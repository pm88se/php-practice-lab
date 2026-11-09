<?php

declare(strict_types=1);

namespace Practice;

/**
 * Plan a safe execution order for tasks with prerequisite relationships.
 */
final class TaskDependencyPlanner
{
    /**
     * Return a deterministic topological order in which each task appears once
     * and every prerequisite precedes the task that depends on it.
     *
     * A task is declared as 'task-id' => ['prerequisite-id', ...].
     * Preserve input order when first selecting tasks with no dependencies,
     * then process newly unblocked tasks in discovery order.
     *
     * Reject unknown prerequisites with InvalidArgumentException.
     * Reject cyclic dependencies with LogicException.
     *
     * @param array<string, list<string>> $tasks
     * @return list<string>
     */
    public function plan(array $tasks): array
    {
        throw new \LogicException('PHP-007 plan() not implemented yet');
    }
}
