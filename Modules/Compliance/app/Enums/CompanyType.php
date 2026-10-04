<?php

namespace Modules\Compliance\Enums;

/** Loại hình tổ chức của pháp nhân chủ hệ thống (hồ sơ Trụ sở chính). */
enum CompanyType: string
{
    case JointStock  = 'joint_stock';
    case Llc         = 'llc';
    case Cooperative = 'cooperative';
    case Household   = 'household';
    case Other       = 'other';

    public function label(): string
    {
        return match ($this) {
            self::JointStock  => 'Công ty Cổ phần',
            self::Llc         => 'Công ty TNHH',
            self::Cooperative => 'Hợp tác xã',
            self::Household   => 'Hộ kinh doanh',
            self::Other       => 'Khác',
        };
    }
}
