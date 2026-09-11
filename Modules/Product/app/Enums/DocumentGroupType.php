<?php

namespace Modules\Product\Enums;

enum DocumentGroupType: string
{
    case LegalFacility  = 'legal_facility';
    case AttpQuality    = 'attp_quality';
    case Personnel      = 'personnel';
    case Traceability   = 'traceability';
    case MonitoringLogs = 'monitoring_logs';

    public function label(): string
    {
        return match ($this) {
            self::LegalFacility  => 'Hồ sơ pháp lý đối với cơ sở',
            self::AttpQuality    => 'ATTP & Chất lượng',
            self::Personnel      => 'Hồ sơ đối với nhân viên',
            self::Traceability   => 'Hồ sơ kiểm soát nguồn gốc nguyên liệu',
            self::MonitoringLogs => 'Sổ sách ghi chép và giám sát tại chỗ',
        };
    }
}
