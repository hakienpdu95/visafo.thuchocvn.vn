<?php

namespace Modules\Product\Models;

use App\Traits\HasAutoCode;
use Illuminate\Database\Eloquent\Builder;
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
    use HasAutoCode;

    public function autoCodeColumn(): string
    {
        return 'code';
    }

    public function autoCodeSequenceType(): string
    {
        return 'document_master_type';
    }

    protected $fillable = [
        'code',
        'name',
        'document_group',
        'applicable_to',
        'is_required_issue_date',
        'is_required_expiry_date',
        'has_expiration_date',
        'has_issue_place',
        'is_transactional',
        'default_validity_months',
    ];

    protected function casts(): array
    {
        return [
            'document_group'          => DocumentGroupType::class,
            'applicable_to'           => 'array',
            'is_required_issue_date'  => 'boolean',
            'is_required_expiry_date' => 'boolean',
            'has_expiration_date'     => 'boolean',
            'has_issue_place'         => 'boolean',
            'is_transactional'        => 'boolean',
            'default_validity_months' => 'integer',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ComplianceDocument::class, 'document_master_type_id');
    }

    /**
     * Lọc loại giấy tờ theo đối tượng áp dụng (vendor | product | partner_product | internal).
     */
    public function scopeApplicableTo(Builder $query, string $context): Builder
    {
        return $query->whereJsonContains('applicable_to', $context);
    }
}
