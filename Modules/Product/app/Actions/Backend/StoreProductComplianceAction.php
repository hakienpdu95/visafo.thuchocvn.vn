<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreProductComplianceData;
use Modules\Product\Enums\ComplianceStatus;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCompliance;

class StoreProductComplianceAction
{
    use AsAction;

    public function handle(Product $product, StoreProductComplianceData $data): ProductCompliance
    {
        return $product->compliances()->create([
            'document_type_id'     => $data->document_type_id,
            'document_number'      => $data->document_number,
            'classification_grade' => $data->classification_grade?->value,
            'issue_date'           => $data->issue_date,
            'expiration_date'      => $data->expiration_date,
            'file_url'             => $data->file_url,
            'pif_file_url'         => $data->pif_file_url,
            'status'               => ComplianceStatus::Active->value,
        ]);
    }
}
