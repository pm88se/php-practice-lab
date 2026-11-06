<?php

declare(strict_types=1);

namespace Practice;

require_once __DIR__ . '/StableFifoPriorityQueue.php';

final class JobScheduler
{
    private StableFifoPriorityQueue $priorityQueue;
    public function __construct()
    {
        $this->priorityQueue = new StableFifoPriorityQueue();
    }
    /**
     * Queue a job. A larger priority number means higher priority.
     *
     * PHP-003: implement priority-based ordering with FIFO for equal priorities.
     */
    public function add(string $id, string $type, int $priority): void
    {
        $this->priorityQueue->insert(['id' => $id, 'type' => $type, 'priority' => $priority], $priority);
    }

    /**
     * Return and remove the next job, or null when empty.
     *
     * @return array{id: string, type: string, priority: int}|null
     */
    public function next(): ?array
    {
        if ($this->priorityQueue->isEmpty()) {
            return null;
        }
        $job = $this->priorityQueue->extract();
        return $job;
    }
}
