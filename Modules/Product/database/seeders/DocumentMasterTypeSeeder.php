<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Models\DocumentMasterType;

class DocumentMasterTypeSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DocumentMasterType::query()->forceDelete();
        Schema::enableForeignKeyConstraints();

        $definitions = $this->definitions();

        foreach ($definitions as $definition) {
            DocumentMasterType::query()->create($definition);
        }

        $this->command?->info('  ✓ document_master_types seeded: ' . count($definitions) . ' loại giấy tờ ATTP bếp ăn bán trú.');
    }

    /**
     * Ranh giới kiến trúc: bảng này CHỈ quản lý Giấy phép/Chứng nhận/Chứng chỉ/Sổ sách.
     * Hợp đồng và phụ lục hợp đồng thuộc về Module Contract (bảng `contracts` +
     * `contract_types`) — không seed bất kỳ loại hợp đồng nào ở đây.
     */
    private function definitions(): array
    {
        return [
            // Nhóm 1: Hồ sơ pháp lý cơ sở — nội bộ Bếp ăn/Nhà hàng
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

            // Nhóm 2: Hồ sơ nhân viên — nội bộ Bếp ăn/Nhà hàng
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

            // Nhóm 3: Hồ sơ nguồn gốc (Truy xuất) — phía Nhà cung cấp
            [
                'code'                     => 'supplier_business_registration',
                'name'                     => 'Giấy chứng nhận đăng ký kinh doanh / ĐKKD',
                'document_group'           => DocumentGroupType::Traceability->value,
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
                'document_group'           => DocumentGroupType::Traceability->value,
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
                'document_group'           => DocumentGroupType::Traceability->value,
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
                'document_group'           => DocumentGroupType::Traceability->value,
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
                'document_group'           => DocumentGroupType::Traceability->value,
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

            // Nhóm 4: Sổ sách giám sát tại chỗ — nội bộ Bếp ăn/Nhà hàng
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
