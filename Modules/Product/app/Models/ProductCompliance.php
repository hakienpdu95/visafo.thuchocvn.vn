<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Enums\ClassificationGrade;
use Modules\Product\Enums\ComplianceStatus;

class ProductCompliance extends Model
{
    use HasUlids;

    public $timestamps = true;

    protected $fillable = [
        'product_id',
        'document_type_id',
        'document_number',
        'classification_grade',
        'issue_date',
        'expiration_date',
        'file_url',
        'pif_file_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'classification_grade' => ClassificationGrade::class,
            'issue_date'            => 'date',
            'expiration_date'       => 'date',
            'status'                => ComplianceStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentMasterType::class, 'document_type_id');
    }

    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->isPast();
    }

    public function isExpiringWithinDays(int $days): bool
    {
        return $this->expiration_date !== null
            && ! $this->isExpired()
            && $this->expiration_date->lte(now()->addDays($days));
    }
}
