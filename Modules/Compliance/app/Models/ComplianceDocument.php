<?php

namespace Modules\Compliance\Models;

use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Product\Models\DocumentMasterType;
use Spatie\MediaLibrary\HasMedia;

class ComplianceDocument extends Model implements HasMedia
{
    use HasUlids;
    use SoftDeletes;
    use HasTenantMedia;

    public $timestamps = true;

    protected $fillable = [
        'document_master_type_id',
        'document_number',
        'classification_grade',
        'issue_date',
        'expiration_date',
        'issued_by',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'       => 'date',
            'expiration_date'  => 'date',
            'status'           => ComplianceDocumentStatus::class,
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentMasterType::class, 'document_master_type_id');
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

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiration_date')->where('expiration_date', '<', now());
    }

    public function scopeExpiringWithinDays(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('expiration_date')
            ->where('expiration_date', '>=', now())
            ->where('expiration_date', '<=', now()->addDays($days));
    }
}
