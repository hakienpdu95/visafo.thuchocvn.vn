<?php

namespace Modules\Product\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use App\Traits\HasAutoCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Enums\AreaUnit;
use Modules\Vendor\Models\Vendor;

class FarmingSource extends TenantAwareModel
{
    use HasCreator;

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
        'area_value',
        'area_unit',
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
            'area_hectare'           => 'decimal:4',
            'area_value'             => 'decimal:2',
            'area_unit'              => AreaUnit::class,
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

    public function permissionModule(): string
    {
        return 'compliance';
    }

    public function areaText(): ?string
    {
        if ($this->area_hectare === null) {
            return null;
        }

        $format = fn (float $n, int $decimals) => rtrim(rtrim(number_format($n, $decimals, ',', '.'), '0'), ',');
        $squareMeters = (float) $this->area_hectare * 10000;

        if ($this->area_unit === null || $this->area_unit === AreaUnit::Hectare) {
            return $format((float) $this->area_hectare, 4) . ' ha';
        }

        if ($this->area_unit === AreaUnit::SquareMeter) {
            return $format($squareMeters, 2) . ' m²';
        }

        return $format((float) $this->area_value, 2) . ' ' . $this->area_unit->shortLabel()
            . ' (' . $format($squareMeters, 2) . ' m²)';
    }
}
