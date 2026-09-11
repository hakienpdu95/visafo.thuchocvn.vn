<?php

namespace Modules\Compliance\Enums;

enum WarningCategory: string
{
    case ProductComplianceExpiry  = 'product_compliance_expiry';
    case VendorCertificateExpiry  = 'vendor_certificate_expiry';
    case BatchNearExpiry          = 'batch_near_expiry';
    case EmployeeHealthRecordExpiry = 'employee_health_record_expiry';
    case InternalFacilityComplianceExpiry = 'internal_facility_compliance_expiry';

    public function label(): string
    {
        return match ($this) {
            self::ProductComplianceExpiry    => 'Hồ sơ pháp lý sản phẩm sắp hết hạn',
            self::VendorCertificateExpiry    => 'Chứng chỉ nhà cung cấp sắp hết hạn',
            self::BatchNearExpiry            => 'Lô hàng cận hạn sử dụng',
            self::EmployeeHealthRecordExpiry => 'Hồ sơ y tế/ATTP nhân viên sắp hết hạn',
            self::InternalFacilityComplianceExpiry => 'Hồ sơ năng lực nội bộ sắp hết hạn',
        };
    }
}
