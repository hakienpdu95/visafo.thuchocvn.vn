<?php

namespace Modules\Compliance\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Compliance\Enums\ComplianceDocumentStatus;

class InternalFacility extends TenantAwareModel
{
    protected $fillable = [
        'name',
        'type',
        'address',
        'status',
    ];

    public function documents(): MorphMany
    {
        return $this->morphMany(ComplianceDocument::class, 'documentable');
    }

    public function activeDocuments(): MorphMany
    {
        return $this->documents()->where('status', ComplianceDocumentStatus::Active->value);
    }

    public function latestDocument(): MorphOne
    {
        return $this->morphOne(ComplianceDocument::class, 'documentable')->ofMany('issue_date', 'max');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'headquarter'      => 'Trụ sở chính',
            'farm'              => 'Vùng trồng',
            'warehouse'         => 'Kho',
            'processing_zone'   => 'Khu sơ chế/chế biến',
            default             => $this->type,
        };
    }
}
