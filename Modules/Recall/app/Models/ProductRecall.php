<?php

namespace Modules\Recall\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Modules\Recall\Enums\RecallSeverity;
use Modules\Recall\Enums\RecallStatus;
use Modules\Warehouse\Models\Batch;

class ProductRecall extends TenantAwareModel
{
    protected $fillable = [
        'product_id',
        'batch_id',
        'reason',
        'severity',
        'status',
        'initiated_by',
        'initiated_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'severity'     => RecallSeverity::class,
            'status'       => RecallStatus::class,
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function isProductWide(): bool
    {
        return $this->batch_id === null;
    }
}
