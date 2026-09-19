<?php

namespace Modules\Vendor\Enums;

enum VendorSourceGroup: string
{
    case N1 = 'n1';
    case N2 = 'n2';
    case N3 = 'n3';

    public function label(): string
    {
        return match ($this) {
            self::N1 => 'N1 · Tự sản xuất',
            self::N2 => 'N2 · Thu gom',
            self::N3 => 'N3 · Chợ đầu mối / siêu thị',
            self::N4 => 'N4 · Doanh nghiệp/ thương mại',
        };
    }
}
