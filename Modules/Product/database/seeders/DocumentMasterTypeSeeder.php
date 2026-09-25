<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Models\DocumentMasterType;

class DocumentMasterTypeSeeder extends Seeder
{
    /**
     * An toàn khi chạy lại trên dữ liệu thật: không xóa, không đổi ID. Loại giấy tờ chưa có
     * (kể cả đã xóa mềm) thì tạo mới; đã có thì chỉ đồng bộ `document_group`, giữ nguyên
     * các chỉnh sửa khác của admin.
     */
    public function run(): void
    {
        $created = 0;
        $synced  = 0;

        foreach ($this->definitions() as $definition) {
            $existing = DocumentMasterType::withTrashed()->where('code', $definition['code'])->first();

            if ($existing === null) {
                DocumentMasterType::query()->create($definition);
                $created++;
                continue;
            }

            if ($existing->document_group?->value !== $definition['document_group']) {
                $existing->update(['document_group' => $definition['document_group']]);
                $synced++;
            }
        }

        $this->command?->info("  ✓ document_master_types: tạo mới {$created}, cập nhật nhóm {$synced}.");
    }

    /**
     * Ranh giới kiến trúc: bảng này CHỈ quản lý Giấy phép/Chứng nhận/Chứng chỉ/Sổ sách.
     * Hợp đồng và phụ lục hợp đồng thuộc về Module Contract (bảng `contracts` +
     * `contract_types`) — không seed bất kỳ loại hợp đồng nào ở đây.
     */
    private function definitions(): array
    {
        return [
            // Nội bộ Visafo, theo Phụ lục 1 QT-TXNG-01
            [
                'code'                     => 'internal_business_registration',
                'name'                     => 'Đăng ký doanh nghiệp',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'internal_capability_profile',
                'name'                     => 'Hồ sơ năng lực VISAFO',
                'document_group'           => DocumentGroupType::Commercial->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'internal_reference_contract',
                'name'                     => 'Hợp đồng tương tự',
                'document_group'           => DocumentGroupType::Commercial->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'internal_price_list',
                'name'                     => 'Bảng giá / Báo giá tiêu chuẩn',
                'document_group'           => DocumentGroupType::Commercial->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => 12,
            ],
            [
                'code'                     => 'internal_records_authorization',
                'name'                     => 'Giấy ủy quyền phụ trách hồ sơ',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'facility_commitment',
                'name'                     => 'Bản cam kết bảo đảm an toàn thực phẩm',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],

            [
                'code'                     => 'facility_attp',
                'name'                     => 'Giấy chứng nhận cơ sở đủ điều kiện ATTP',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 36,
            ],
            [
                'code'                     => 'internal_haccp',
                'name'                     => 'Chứng nhận HACCP',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 36,
            ],
            [
                'code'                     => 'internal_soil_test',
                'name'                     => 'Báo cáo phân tích mẫu đất',
                'document_group'           => DocumentGroupType::Traceability->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 12,
            ],
            [
                'code'                     => 'internal_water_test',
                'name'                     => 'Báo cáo phân tích mẫu nước',
                'document_group'           => DocumentGroupType::Traceability->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 12,
            ],
            [
                'code'                     => 'internal_flow_diagram',
                'name'                     => 'Sơ đồ quy trình một chiều',
                'document_group'           => DocumentGroupType::MonitoringLogs->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => false,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],

            [
                'code'                     => 'internal_staff_list',
                'name'                     => 'Danh sách nhân sự trực tiếp',
                'document_group'           => DocumentGroupType::Personnel->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => false,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'personnel_training',
                'name'                     => 'Giấy xác nhận kiến thức về an toàn thực phẩm',
                'document_group'           => DocumentGroupType::Personnel->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 36,
            ],
            [
                'code'                     => 'personnel_periodic_health',
                'name'                     => 'Khám sức khỏe định kỳ',
                'document_group'           => DocumentGroupType::Personnel->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 12,
            ],
            [
                'code'                     => 'internal_quality_assignment',
                'name'                     => 'Phân công phụ trách chất lượng',
                'document_group'           => DocumentGroupType::Personnel->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => false,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],

            [
                'code'                     => 'personnel_health',
                'name'                     => 'Giấy khám sức khỏe',
                'document_group'           => DocumentGroupType::Personnel->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 12,
            ],

            // Phía Nhà cung cấp / sản phẩm
            [
                'code'                     => 'supplier_business_registration',
                'name'                     => 'Giấy chứng nhận đăng ký kinh doanh / ĐKKD',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['vendor'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'personal_id_card',
                'name'                     => 'Căn cước công dân (hộ kinh doanh cá thể)',
                'document_group'           => DocumentGroupType::Personnel->value,
                'applicable_to'            => ['vendor'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'supplier_attp',
                'name'                     => 'Giấy chứng nhận cơ sở đủ điều kiện ATTP (NCC)',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['vendor'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 36,
            ],
            [
                'code'                     => 'supplier_commitment',
                'name'                     => 'Bản cam kết bảo đảm an toàn thực phẩm (Phía NCC)',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['vendor'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'supplier_vietgap',
                'name'                     => 'Chứng nhận VietGAP / GlobalGAP',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['vendor', 'product', 'partner_product'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 12,
            ],
            [
                'code'                     => 'supplier_gmp',
                'name'                     => 'Giấy chứng nhận Thực hành sản xuất tốt (GMP)',
                'document_group'           => DocumentGroupType::LegalFacility->value,
                'applicable_to'            => ['vendor', 'product', 'partner_product'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 36,
            ],
            [
                'code'                     => 'supplier_vet',
                'name'                     => 'Giấy chứng nhận kiểm dịch thú y',
                'document_group'           => DocumentGroupType::Traceability->value,
                'applicable_to'            => ['vendor', 'product', 'partner_product'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => true,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'supplier_ocop',
                'name'                     => 'Giấy chứng nhận OCOP',
                'document_group'           => DocumentGroupType::Traceability->value,
                'applicable_to'            => ['vendor', 'product', 'partner_product'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => true,
                'has_expiration_date'      => true,
                'has_issue_place'          => true,
                'is_transactional'         => false,
                'default_validity_months'  => 36,
            ],
            [
                'code'                     => 'product_declaration',
                'name'                     => 'Hồ sơ công bố sản phẩm (Tự công bố / Đăng ký)',
                'document_group'           => DocumentGroupType::Traceability->value,
                'applicable_to'            => ['product', 'partner_product'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'daily_invoice',
                'name'                     => 'Chứng từ giao nhận hàng ngày (Hóa đơn, phiếu xuất/nhập)',
                'document_group'           => DocumentGroupType::Traceability->value,
                'applicable_to'            => ['vendor'],
                'is_required_issue_date'   => true,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => true,
                'default_validity_months'  => null,
            ],

            // Sổ sách giám sát tại chỗ — nội bộ Bếp ăn/Nhà hàng
            [
                'code'                     => 'log_3_steps',
                'name'                     => 'Sổ Kiểm thực 3 bước (QĐ 1246/QĐ-BYT)',
                'document_group'           => DocumentGroupType::MonitoringLogs->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => false,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'log_sample',
                'name'                     => 'Sổ lưu mẫu thức ăn',
                'document_group'           => DocumentGroupType::MonitoringLogs->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => false,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
            [
                'code'                     => 'log_chemical',
                'name'                     => 'Sổ theo dõi hóa chất, vật tư y tế',
                'document_group'           => DocumentGroupType::MonitoringLogs->value,
                'applicable_to'            => ['internal'],
                'is_required_issue_date'   => false,
                'is_required_expiry_date'  => false,
                'has_expiration_date'      => false,
                'has_issue_place'          => false,
                'is_transactional'         => false,
                'default_validity_months'  => null,
            ],
        ];
    }
}
