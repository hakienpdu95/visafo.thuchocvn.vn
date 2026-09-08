<?php

namespace Modules\Warehouse\Enums;

enum BatchDocumentType: string
{
    case Coa         = 'coa';
    case QualityTest = 'quality_test';

    public function label(): string
    {
        return match ($this) {
            self::Coa         => 'Giấy chứng nhận phân tích (COA)',
            self::QualityTest => 'Phiếu kiểm nghiệm chất lượng',
        };
    }
}
