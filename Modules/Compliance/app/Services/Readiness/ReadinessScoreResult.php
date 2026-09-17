<?php

namespace Modules\Compliance\Services\Readiness;

final class ReadinessScoreResult
{
    /**
     * @param ReadinessCategoryResult[] $categories
     * @param array<int, array{category: string, label: string, note: ?string, pointsLost: float}> $topIssues
     */
    public function __construct(
        public readonly int $totalScore,
        public readonly int $maxScore,
        public readonly array $categories,
        public readonly array $topIssues,
    ) {}

    public function percent(): int
    {
        return $this->maxScore > 0 ? (int) round($this->totalScore / $this->maxScore * 100) : 0;
    }

    public function verdictLabel(): string
    {
        return match (true) {
            $this->totalScore >= 85 => 'Sẵn sàng chào hàng',
            $this->totalScore >= 60 => 'Cần hoàn thiện trước khi chào',
            default                 => 'Chưa đủ điều kiện chào hàng',
        };
    }

    public function verdictClass(): string
    {
        return match (true) {
            $this->totalScore >= 85 => 'text-success',
            $this->totalScore >= 60 => 'text-warning',
            default                 => 'text-error',
        };
    }
}
