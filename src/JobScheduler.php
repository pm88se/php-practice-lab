<?php

declare(strict_types=1);

namespace Practice;

final class JobScheduler
{
    /**
     * Queue a job. A larger priority number means higher priority.
     *
     * PHP-003: implement priority-based ordering with FIFO for equal priorities.
     */
    public function add(string $id, string $type, int $priority): void
    {
        throw new \LogicException('PHP-003 add() not implemented yet');
    }

    /**
     * Return and remove the next job, or null when empty.
     *
     * @return array{id: string, type: string, priority: int}|null
     */
    public function next(): ?array
    {
        throw new \LogicException('PHP-003 next() not implemented yet');
    }
}
