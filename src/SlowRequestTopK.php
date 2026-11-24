<?php
declare(strict_types=1);

namespace Practice;

final class RequestMinHeap extends \SplHeap
{
    protected function compare(mixed $a, mixed $b): int
    {
        if ($a['duration_ms'] === $b['duration_ms']) {
            return $a['sequence'] <=> $b['sequence'];
        }

        return $b['duration_ms'] <=> $a['duration_ms'];
    }

    public function isBetterThanWorst(array $candidate): bool
    {
        return $this->compare($candidate, $this->top()) < 0;
    }
}

final class SlowRequestTopK
{
    private RequestMinHeap $heap;
    private int $sequence = 0;

    public function __construct(
        private readonly int $capacity
    ) {
        if ($capacity <= 0) {
            throw new \InvalidArgumentException(
                'Capacity must be greater than zero'
            );
        }

        $this->heap = new RequestMinHeap();
    }

    public function add(
        string $id,
        string $route,
        int $durationMs
    ): void {
        $data = [
            'id' => $id,
            'route' => $route,
            'duration_ms' => $durationMs,
            'sequence' => $this->sequence++,
        ];

        if ($this->heap->count() < $this->capacity) {
            $this->heap->insert($data);
            return;
        }

        if ($this->heap->isBetterThanWorst($data)) {
            $this->heap->extract();
            $this->heap->insert($data);
        }
    }

    public function count(): int
    {
        return $this->heap->count();
    }

    public function drain(): array
    {
        $result = [];

        while (!$this->heap->isEmpty()) {
            $result[] = $this->heap->extract();
        }

        usort($result, static function (array $a, array $b): int {
            $durationComparison =
                $b['duration_ms'] <=> $a['duration_ms'];

            if ($durationComparison !== 0) {
                return $durationComparison;
            }

            return $a['sequence'] <=> $b['sequence'];
        });

        return array_map(
            static fn(array $item): array => [
                'id' => $item['id'],
                'route' => $item['route'],
                'duration_ms' => $item['duration_ms'],
            ],
            $result
        );
    }
}
