<?php

declare(strict_types=1);

namespace Practice;

use SplPriorityQueue;

final class StableFifoPriorityQueue extends SplPriorityQueue
{
    private int $serial = PHP_INT_MAX;

    #[\Override]
    public function insert(mixed $value, mixed $priority): true 
    {
        parent::insert($value, [$priority, $this->serial--]);
        return true;
    }

    #[\Override]
    public function compare(mixed $priority1, mixed $priority2): int 
    {
        if ($priority1[0] === $priority2[0]) {
            return $priority1[1] <=> $priority2[1];
        }
        
        return $priority1[0] <=> $priority2[0];
    }
}
