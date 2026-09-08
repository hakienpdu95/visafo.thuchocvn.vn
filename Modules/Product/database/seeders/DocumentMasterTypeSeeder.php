<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Enums\ProductCategoryType;
use Modules\Product\Models\DocumentMasterType;

class DocumentMasterTypeSeeder extends Seeder
{
    public function run(): void
    {
        DocumentMasterType::where('code', 'cosmetic_pif')->update(['code' => 'cosmetic_notification']);

        foreach ($this->definitions() as $definition) {
            DocumentMasterType::query()->updateOrCreate(
                ['code' => $definition['code']],
                $definition,
            );
        }

        $this->command?->info('  ✓ document_master_types seeded: ' . count($this->definitions()) . ' loại giấy tờ.');
    }

    private function definitions(): array
    {
        return [
            [
                'code'                    => 'food_self_declaration',
                'name'                    => 'Bản tự công bố sản phẩm (NĐ 15/2018)',
                'applicable_category'     => ProductCategoryType::Food->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => false,
                'default_validity_months' => null,
            ],
            [
                'code'                    => 'ocop_certificate',
                'name'                    => 'Giấy chứng nhận sản phẩm OCOP (QĐ 148/QĐ-TTg)',
                'applicable_category'     => ProductCategoryType::Food->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => true,
                'default_validity_months' => 36,
            ],
            [
                'code'                    => 'vietgap_globalgap',
                'name'                    => 'Chứng nhận VietGAP / GlobalGAP',
                'applicable_category'     => ProductCategoryType::Food->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => true,
                'default_validity_months' => 12,
            ],
            [
                'code'                    => 'food_registration_declaration',
                'name'                    => 'Giấy tiếp nhận đăng ký bản công bố sản phẩm (Sữa công thức)',
                'applicable_category'     => ProductCategoryType::Food->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => true,
                'default_validity_months' => 60,
            ],
            [
                'code'                    => 'cosmetic_notification',
                'name'                    => 'Số tiếp nhận Phiếu công bố sản phẩm mỹ phẩm (kèm PIF)',
                'applicable_category'     => ProductCategoryType::Cosmetic->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => true,
                'default_validity_months' => 60,
            ],
            [
                'code'                    => 'medical_device_classification',
                'name'                    => 'Bản phân loại trang thiết bị y tế (A/B/C/D) & Số lưu hành',
                'applicable_category'     => ProductCategoryType::MedicalDevice->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => false,
                'default_validity_months' => null,
            ],
            [
                'code'                    => 'medical_standard_declaration',
                'name'                    => 'Số phiếu tiếp nhận hồ sơ công bố tiêu chuẩn áp dụng (TBYT loại A/B)',
                'applicable_category'     => ProductCategoryType::MedicalDevice->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => false,
                'default_validity_months' => null,
            ],
            [
                'code'                    => 'toy_cr_cert',
                'name'                    => 'Giấy chứng nhận Hợp quy CR (QCVN 3:2019/BKHCN)',
                'applicable_category'     => ProductCategoryType::ToyPlastic->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => true,
                'default_validity_months' => 36,
            ],
            [
                'code'                    => 'textile_conformity_declaration',
                'name'                    => 'Bản công bố hợp quy dệt may (QCVN 01:2017/BCT)',
                'applicable_category'     => ProductCategoryType::Textile->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => false,
                'default_validity_months' => null,
            ],
            [
                'code'                    => 'tccs_standard',
                'name'                    => 'Bản công bố Tiêu chuẩn cơ sở (TCCS)',
                'applicable_category'     => ProductCategoryType::ConsumerGoods->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => false,
                'default_validity_months' => null,
            ],
            [
                'code'                    => 'food_contact_self_declaration',
                'name'                    => 'Bản tự công bố sản phẩm — vật liệu tiếp xúc thực phẩm (QCVN 12-1:2011/BYT)',
                'applicable_category'     => ProductCategoryType::FoodContactMaterial->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => false,
                'default_validity_months' => null,
            ],
            [
                'code'                    => 'electrical_cr_cert',
                'name'                    => 'Giấy chứng nhận hợp quy thiết bị điện (Dấu CR — QCVN 4:2009/BKHCN)',
                'applicable_category'     => ProductCategoryType::ElectricalAppliance->value,
                'is_required_issue_date'  => true,
                'is_required_expiry_date' => true,
                'default_validity_months' => 36,
            ],
        ];
    }
}
