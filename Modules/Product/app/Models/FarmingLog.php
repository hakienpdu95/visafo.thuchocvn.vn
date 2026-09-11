<?php

namespace Modules\Product\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmingLog extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'farming_batch_id',
        'activity_type',
        'activity_date',
        'agri_fertilizer_id',
        'agri_pesticide_id',
        'vendor_farming_step_id',
        'quantity',
        'unit',
        'method_or_target',
        'safe_harvest_date',
        'image_path',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'activity_date'     => 'datetime',
            'quantity'          => 'decimal:2',
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

    public function agriFertilizer(): BelongsTo
    {
        return $this->belongsTo(AgriFertilizer::class);
    }

    public function agriPesticide(): BelongsTo
    {
        return $this->belongsTo(AgriPesticide::class);
    }

    public function vendorFarmingStep(): BelongsTo
    {
        return $this->belongsTo(VendorFarmingStep::class);
    }
}
