<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StorePartnerProductComplianceData;
use Modules\Product\Enums\ComplianceStatus;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\PartnerProductCompliance;

class StorePartnerProductComplianceAction
{
    use AsAction;

    public function handle(PartnerProduct $partnerProduct, StorePartnerProductComplianceData $data): PartnerProductCompliance
    {
        return $partnerProduct->compliances()->create([
            'document_type_id' => $data->document_type_id,
            'document_number'  => $data->document_number,
            'issue_date'       => $data->issue_date,
            'expiration_date'  => $data->expiration_date,
            'file_url'         => $data->file_url,
            'status'           => ComplianceStatus::Active->value,
        ]);
    }
}
