<?php

namespace Modules\Warehouse\Enums;

enum BatchStatus: string
{
    case Available   = 'available';
    case Quarantined = 'quarantined';
    case Recalled    = 'recalled';
    case OutOfStock  = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::Available   => 'Đang bán',
            self::Quarantined => 'Cách ly / Chờ kiểm tra',
            self::Recalled    => 'Đã thu hồi',
            self::OutOfStock  => 'Hết hàng',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Available   => 'badge-success',
            self::Quarantined => 'badge-warning',
            self::Recalled    => 'badge-error',
            self::OutOfStock  => 'badge-neutral',
        };
    }
}
