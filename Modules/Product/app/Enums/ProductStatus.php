<?php

namespace Modules\Product\Enums;

enum ProductStatus: string
{
    case Active       = 'active';
    case Discontinued = 'discontinued';

    public function label(): string
    {
        return match ($this) {
            self::Active       => 'Đang kinh doanh',
            self::Discontinued => 'Ngừng kinh doanh',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active       => 'badge-success',
            self::Discontinued => 'badge-neutral',
        };
    }
}
