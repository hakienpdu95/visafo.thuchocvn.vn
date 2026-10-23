<?php

namespace Modules\Vendor\Enums;

enum VendorSupplyGroup: string
{
    case Produce       = 'produce';
    case FreshFood     = 'fresh_food';
    case DryGoods      = 'dry_goods';
    case ProcessedFood = 'processed_food';

    public function label(): string
    {
        return match ($this) {
            self::Produce       => 'Rau, Củ, Quả',
            self::FreshFood     => 'Thực Phẩm Tươi Sống',
            self::DryGoods      => 'Đồ Khô',
            self::ProcessedFood => 'Thực Phẩm Chế Biến',
        };
    }
}
