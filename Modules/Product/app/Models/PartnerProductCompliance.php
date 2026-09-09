<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Enums\ComplianceStatus;

class PartnerProductCompliance extends Model
{
    use HasUlids;

    protected $fillable = [
        'partner_product_id',
        'document_type_id',
        'document_number',
        'issue_date',
        'expiration_date',
        'file_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'      => 'date',
            'expiration_date' => 'date',
            'status'          => ComplianceStatus::class,
        ];
    }

    public function partnerProduct(): BelongsTo
    {
        return $this->belongsTo(PartnerProduct::class);
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
