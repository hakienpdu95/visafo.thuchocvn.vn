<?php

namespace Modules\Vendor\Enums;

enum VendorStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Đang hợp tác',
            self::Inactive => 'Ngừng hợp tác',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active   => 'badge-success',
            self::Inactive => 'badge-neutral',
        };
    }
}
