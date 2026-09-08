<?php

namespace Modules\Recall\Enums;

enum RecallStatus: string
{
    case Active    = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'Đang thu hồi',
            self::Completed => 'Đã hoàn tất',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active    => 'badge-error',
            self::Completed => 'badge-success',
            self::Cancelled => 'badge-neutral',
        };
    }
}
