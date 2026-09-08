<?php

namespace Modules\Recall\Enums;

enum AdverseEventReportSource: string
{
    case HealthcareProfessional = 'healthcare_professional';
    case Customer                = 'customer';
    case Other                   = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HealthcareProfessional => 'Chuyên gia y tế',
            self::Customer                => 'Khách hàng',
            self::Other                   => 'Nguồn khác',
        };
    }
}
