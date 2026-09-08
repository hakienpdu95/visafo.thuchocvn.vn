<?php

namespace Modules\Compliance\Enums;

enum WarningStatus: string
{
    case Pending      = 'pending';
    case Acknowledged = 'acknowledged';
    case Resolved     = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending      => 'Chưa xử lý',
            self::Acknowledged => 'Đã ghi nhận',
            self::Resolved     => 'Đã xử lý xong',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending      => 'badge-error',
            self::Acknowledged => 'badge-warning',
            self::Resolved     => 'badge-success',
        };
    }
}
