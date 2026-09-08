<?php

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraceabilityScanLog extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'retail_item_tag_id',
        'scanned_code',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function retailItemTag(): BelongsTo
    {
        return $this->belongsTo(RetailItemTag::class);
    }
}
