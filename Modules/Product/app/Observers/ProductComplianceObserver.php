<?php

namespace Modules\Product\Observers;

use Modules\Product\Enums\ComplianceStatus;
use Modules\Product\Models\ProductCompliance;

class ProductComplianceObserver
{
    public function creating(ProductCompliance $compliance): void
    {
        if ($compliance->status instanceof ComplianceStatus
            ? $compliance->status !== ComplianceStatus::Active
            : $compliance->status !== ComplianceStatus::Active->value) {
            return;
        }

        ProductCompliance::where('product_id', $compliance->product_id)
            ->where('document_type_id', $compliance->document_type_id)
            ->where('status', ComplianceStatus::Active->value)
            ->update(['status' => ComplianceStatus::Superseded->value]);
    }
}
