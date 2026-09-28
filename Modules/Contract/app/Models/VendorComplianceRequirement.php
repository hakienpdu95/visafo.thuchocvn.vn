<?php

namespace Modules\Contract\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contract\Enums\ComplianceRequirementKind;
use Modules\Product\Models\DocumentMasterType;
use Modules\Vendor\Enums\VendorSourceGroup;

class VendorComplianceRequirement extends TenantAwareModel
{
    protected $fillable = [
        'kind',
        'document_master_type_id',
        'contract_type_id',
        'source_group',
        'group_key',
        'label',
        'is_mandatory',
        'warning_days',
        'legal_basis',
    ];

    protected function casts(): array
    {
        return [
            'kind'         => ComplianceRequirementKind::class,
            'source_group' => VendorSourceGroup::class,
            'is_mandatory' => 'boolean',
            'warning_days' => 'integer',
        ];
    }

    public function groupIdentifier(): string
    {
        return $this->group_key ?? $this->id;
    }

    public function scopeInGroup(Builder $query, string $groupKey): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('group_key', $groupKey)->orWhere(
            fn (Builder $sub) => $sub->whereNull('group_key')->whereKey($groupKey)
        ));
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentMasterType::class, 'document_master_type_id');
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }
}
