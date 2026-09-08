<?php

namespace Modules\Vendor\Enums;

enum VendorCertificateType: string
{
    case BusinessRegistration = 'business_registration';
    case FoodSafety           = 'food_safety';
    case Gmp                  = 'gmp';

    public function label(): string
    {
        return match ($this) {
            self::BusinessRegistration => 'Giấy chứng nhận đăng ký kinh doanh',
            self::FoodSafety           => 'Giấy chứng nhận cơ sở đủ điều kiện ATTP',
            self::Gmp                  => 'Giấy chứng nhận Thực hành sản xuất tốt (GMP)',
        };
    }
}
