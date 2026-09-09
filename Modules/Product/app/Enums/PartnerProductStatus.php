<?php

namespace Modules\Product\Enums;

enum PartnerProductStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Đang cung cấp',
            self::Inactive => 'Ngừng cung cấp',
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
