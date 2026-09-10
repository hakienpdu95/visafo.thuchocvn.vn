<?php

namespace Modules\Customer\Enums;

enum CustomerGroup: string
{
    case Kindergarten     = 'kindergarten';
    case HighSchool       = 'high_school';
    case IndustrialCanteen = 'industrial_canteen';
    case TradingCompany   = 'trading_company';

    public function label(): string
    {
        return match ($this) {
            self::Kindergarten      => 'Trường Mầm non',
            self::HighSchool        => 'Trường Phổ thông',
            self::IndustrialCanteen => 'Bếp ăn công nghiệp',
            self::TradingCompany    => 'Doanh nghiệp thương mại',
        };
    }
}
