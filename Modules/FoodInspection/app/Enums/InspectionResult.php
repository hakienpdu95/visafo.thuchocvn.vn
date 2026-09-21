<?php

namespace Modules\FoodInspection\Enums;

enum InspectionResult: string
{
    case Pass = 'pass';
    case Fail = 'fail';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Đạt',
            self::Fail => 'Không đạt',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pass => 'badge-success',
            self::Fail => 'badge-error',
        };
    }
}
