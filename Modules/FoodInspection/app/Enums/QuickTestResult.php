<?php

namespace Modules\FoodInspection\Enums;

enum QuickTestResult: string
{
    case None = 'none';
    case Pass = 'pass';
    case Fail = 'fail';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Không có',
            self::Pass => 'Đạt',
            self::Fail => 'Không đạt',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::None => 'badge-ghost',
            self::Pass => 'badge-success',
            self::Fail => 'badge-error',
        };
    }
}
