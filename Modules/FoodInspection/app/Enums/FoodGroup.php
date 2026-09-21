<?php

namespace Modules\FoodInspection\Enums;

enum FoodGroup: string
{
    case Fresh = 'fresh';
    case Dry   = 'dry';

    public function label(): string
    {
        return match ($this) {
            self::Fresh => 'Thực phẩm tươi sống, đông lạnh',
            self::Dry   => 'Thực phẩm khô, bao gói sẵn, gia vị',
        };
    }
}
