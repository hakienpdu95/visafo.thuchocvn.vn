<?php

namespace Modules\Compliance\Enums;

enum ReadinessStatus: string
{
    case Ready    = 'ready';
    case Warning  = 'warning';
    case NotReady = 'not_ready';

    public function label(): string
    {
        return match ($this) {
            self::Ready    => 'Đủ điều kiện',
            self::Warning  => 'Sắp hết hạn',
            self::NotReady => 'Thiếu hồ sơ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Ready    => 'badge-success',
            self::Warning  => 'badge-warning',
            self::NotReady => 'badge-error',
        };
    }

    public function dotEmoji(): string
    {
        return match ($this) {
            self::Ready    => '🟢',
            self::Warning  => '🟡',
            self::NotReady => '🔴',
        };
    }
}
