<?php

namespace Modules\GoodsReceipt\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\TraceReview;
use Modules\Vendor\Models\Vendor;

class GoodsReceipt extends TenantAwareModel
{
    use HasCreator;

    public const MANUAL_REF_PREFIX = 'PN';

    protected $fillable = [
        'misa_ref_id',
        'vendor_id',
        'supplier_name',
        'receipt_date',
        'source_file_name',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ComplianceDocument::class, 'documentable');
    }

    public function isManual(): bool
    {
        return $this->source_file_name === null;
    }

    public function isEditable(): bool
    {
        $batchIds = ProductBatch::withTrashed()->where('goods_receipt_id', $this->id)->pluck('id');

        if ($batchIds->isEmpty()) {
            return true;
        }

        return ! PrintLog::withTrashed()->whereIn('product_batch_id', $batchIds)->exists()
            && ! TraceReview::withTrashed()->whereIn('product_batch_id', $batchIds)->exists()
            && ! BatchQualityCheck::withTrashed()->whereIn('product_batch_id', $batchIds)->exists();
    }

    public function permissionModule(): string
    {
        return 'goods_receipt';
    }
}
