<?php

namespace Modules\Contract\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Contract\Enums\ComplianceRequirementKind;
use Modules\Contract\Models\ContractType;
use Modules\Contract\Models\VendorComplianceRequirement;
use Modules\Contract\Support\VendorComplianceCache;
use Modules\Product\Models\DocumentMasterType;

class VendorComplianceRequirementSeeder extends Seeder
{
    public function run(): void
    {
        VendorComplianceRequirement::query()->forceDelete();

        $documentTypes = DocumentMasterType::query()->pluck('id', 'code');
        $contractTypes = ContractType::query()->pluck('id', 'code');

        $created = 0;
        foreach ($this->definitions() as $definition) {
            $isContract = $definition['kind'] === ComplianceRequirementKind::Contract;
            $refId      = $isContract ? $contractTypes->get($definition['code']) : $documentTypes->get($definition['code']);

            if ($refId === null) {
                $this->command?->warn("  ! Bỏ qua yêu cầu \"{$definition['label']}\": không tìm thấy mã {$definition['code']}.");
                continue;
            }

            VendorComplianceRequirement::query()->create([
                'kind'                    => $definition['kind'],
                'document_master_type_id' => $isContract ? null : $refId,
                'contract_type_id'        => $isContract ? $refId : null,
                'source_group'            => null,
                'group_key'               => $definition['code'],
                'label'                   => $definition['label'],
                'is_mandatory'            => true,
                'warning_days'            => 30,
            ]);
            $created++;
        }

        VendorComplianceCache::bump();

        $this->command?->info("  ✓ vendor_compliance_requirements seeded: {$created} yêu cầu.");
    }

    private function definitions(): array
    {
        return [
            ['kind' => ComplianceRequirementKind::Document, 'code' => 'supplier_business_registration', 'label' => 'Giấy chứng nhận ĐKKD'],
            ['kind' => ComplianceRequirementKind::Document, 'code' => 'supplier_commitment',            'label' => 'Bản cam kết ATTP'],
            ['kind' => ComplianceRequirementKind::Contract, 'code' => 'framework_agreement',            'label' => 'Hợp đồng nguyên tắc'],
        ];
    }
}
