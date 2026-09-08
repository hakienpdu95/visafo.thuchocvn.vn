<?php

namespace Modules\Compliance\Enums;

enum WarningCategory: string
{
    case ProductComplianceExpiry  = 'product_compliance_expiry';
    case VendorCertificateExpiry  = 'vendor_certificate_expiry';
    case BatchNearExpiry          = 'batch_near_expiry';

    public function label(): string
    {
        return match ($this) {
            self::ProductComplianceExpiry => 'Hồ sơ pháp lý sản phẩm sắp hết hạn',
            self::VendorCertificateExpiry => 'Chứng chỉ nhà cung cấp sắp hết hạn',
            self::BatchNearExpiry         => 'Lô hàng cận hạn sử dụng',
        };
    }
}
