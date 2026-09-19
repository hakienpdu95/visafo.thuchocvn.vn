<?php

namespace Modules\SalesOrder\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintLog extends TenantAwareModel
{
    protected $fillable = [
        'order_item_id',
        'weight_per_label',
        'label_count',
        'mfg_date',
        'exp_date',
        'supplier_name',
        'printed_by',
    ];

    protected function casts(): array
    {
        return [
            'weight_per_label' => 'decimal:3',
            'label_count'      => 'integer',
            'mfg_date'         => 'date',
            'exp_date'         => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'order_item_id');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
