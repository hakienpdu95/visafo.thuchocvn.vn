<?php

namespace Modules\Compliance\Services\Readiness;

final class ReadinessCategoryResult
{
    /**
     * @param ReadinessChecklistItem[] $items
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly int $score,
        public readonly int $maxScore,
        public readonly array $items,
    ) {}

    public function percent(): int
    {
        return $this->maxScore > 0 ? (int) round($this->score / $this->maxScore * 100) : 0;
    }
}
