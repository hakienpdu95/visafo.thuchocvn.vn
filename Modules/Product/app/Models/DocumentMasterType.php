<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Enums\ProductCategoryType;

class DocumentMasterType extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'applicable_category',
        'is_required_issue_date',
        'is_required_expiry_date',
        'default_validity_months',
    ];

    protected function casts(): array
    {
        return [
            'applicable_category'     => ProductCategoryType::class,
            'is_required_issue_date'  => 'boolean',
            'is_required_expiry_date' => 'boolean',
            'default_validity_months' => 'integer',
        ];
    }

    public function compliances(): HasMany
    {
        return $this->hasMany(ProductCompliance::class, 'document_type_id');
    }

    public function requiresPifAttachment(): bool
    {
        return $this->code === 'cosmetic_notification';
    }
}
