<?php

namespace Modules\Product\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use App\Traits\HasAutoCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Vendor\Models\Vendor;

class FarmingSource extends TenantAwareModel
{
    use HasAutoCode;

    public function autoCodeColumn(): string
    {
        return 'source_code';
    }

    public function autoCodeSequenceType(): string
    {
        return 'farming_source';
    }

    protected $fillable = [
        'vendor_id',
        'source_code',
        'name',
        'area_hectare',
        'water_source',
        'address',
        'status',
        'pre_season_checked_at',
        'pre_season_checked_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'area_hectare'           => 'decimal:2',
            'pre_season_checked_at'  => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function preSeasonCheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pre_season_checked_by');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(FarmingBatch::class);
    }

    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }
}
