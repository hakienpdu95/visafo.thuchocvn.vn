<?php

namespace Modules\Compliance\Services\Readiness;

final class ReadinessChecklistItem
{
    public function __construct(
        public readonly string $label,
        public readonly bool $passed,
        public readonly ?string $note,
        public readonly float $pointsLost = 0,
    ) {}
}
