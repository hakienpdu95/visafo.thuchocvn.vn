<?php

namespace Modules\Recall\Enums;

enum AdverseEventStatus: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Closed    = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp',
            self::Submitted => 'Đã nộp Cục QLD',
            self::Closed    => 'Đã đóng',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'badge-neutral',
            self::Submitted => 'badge-warning',
            self::Closed    => 'badge-success',
        };
    }
}
