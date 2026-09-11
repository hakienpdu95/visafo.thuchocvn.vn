<?php

namespace Modules\Product\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmingLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'farming_batch_id',
        'activity_type',
        'activity_date',
        'details',
        'safe_harvest_date',
        'image_path',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'activity_date'     => 'datetime',
            'details'           => 'array',
            'safe_harvest_date' => 'date',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FarmingBatch::class, 'farming_batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
