<?php

namespace Modules\Product\Enums;

enum ProductType: string
{
    case RawMaterial = 'raw_material';
    case FinishedGood = 'finished_good';
    case TradingGood = 'trading_good';

    public function label(): string
    {
        return match ($this) {
            self::RawMaterial  => 'Nguyên liệu đầu vào',
            self::FinishedGood => 'Thành phẩm đầu ra',
            self::TradingGood  => 'Hàng thương mại',
        };
    }
}
