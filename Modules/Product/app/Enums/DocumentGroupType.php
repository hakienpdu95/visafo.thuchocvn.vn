<?php

namespace Modules\Product\Enums;

enum DocumentGroupType: string
{
    case LegalFacility  = 'legal_facility';
    case Traceability   = 'traceability';
    case Personnel      = 'personnel';
    case MonitoringLogs = 'monitoring_logs';
    case Commercial     = 'commercial';

    public function label(): string
    {
        return match ($this) {
            self::LegalFacility  => 'Hồ sơ pháp lý cơ sở',
            self::Traceability   => 'Pháp lý sản phẩm & nguồn gốc',
            self::Personnel      => 'Hồ sơ nhân sự & y tế',
            self::MonitoringLogs => 'Sổ sách vận hành & giám sát',
            self::Commercial     => 'Năng lực thương mại',
        };
    }
}
