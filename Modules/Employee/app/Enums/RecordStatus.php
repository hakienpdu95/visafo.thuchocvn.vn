<?php

namespace Modules\Employee\Enums;

enum RecordStatus: string
{
    case Valid    = 'valid';
    case Expiring = 'expiring';
    case Expired  = 'expired';
    case Missing  = 'missing';

    public function label(): string
    {
        return match ($this) {
            self::Valid    => 'Còn hạn',
            self::Expiring => 'Sắp hết hạn',
            self::Expired  => 'Hết hạn',
            self::Missing  => 'Chưa có',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Valid    => 'badge-success',
            self::Expiring => 'badge-warning',
            self::Expired, self::Missing => 'badge-error',
        };
    }
}
