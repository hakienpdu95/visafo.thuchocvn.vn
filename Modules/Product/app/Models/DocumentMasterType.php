<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Product\Enums\DocumentGroupType;

class DocumentMasterType extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'document_group',
        'is_required_issue_date',
        'is_required_expiry_date',
        'default_validity_months',
    ];

    protected function casts(): array
    {
        return [
            'document_group'          => DocumentGroupType::class,
            'is_required_issue_date'  => 'boolean',
            'is_required_expiry_date' => 'boolean',
            'default_validity_months' => 'integer',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ComplianceDocument::class, 'document_master_type_id');
    }
}
