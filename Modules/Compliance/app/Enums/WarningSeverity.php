<?php

namespace Modules\Compliance\Enums;

enum WarningSeverity: string
{
    case Warning  = 'warning';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Warning  => 'Cảnh báo',
            self::Critical => 'Khẩn cấp',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Warning  => 'badge-warning',
            self::Critical => 'badge-error',
        };
    }
}
