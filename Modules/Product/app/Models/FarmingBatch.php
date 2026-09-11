<?php

namespace Modules\Product\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use App\Traits\HasAutoCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Vendor\Models\Vendor;

class FarmingBatch extends TenantAwareModel
{
    use HasAutoCode;

    public function autoCodeColumn(): string
    {
        return 'batch_code';
    }

    public function autoCodeSequenceType(): string
    {
        return 'farming_batch';
    }

    protected $fillable = [
        'farming_source_id',
        'vendor_id',
        'agri_seed_id',
        'partner_product_id',
        'batch_code',
        'sowing_date',
        'expected_harvest_date',
        'actual_harvest_date',
        'status',
        'pre_harvest_status',
        'pre_harvest_checked_at',
        'pre_harvest_checked_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sowing_date'             => 'date',
            'expected_harvest_date'   => 'date',
            'actual_harvest_date'     => 'date',
            'pre_harvest_checked_at'  => 'datetime',
        ];
    }

    public function farmingSource(): BelongsTo
    {
        return $this->belongsTo(FarmingSource::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function agriSeed(): BelongsTo
    {
        return $this->belongsTo(AgriSeed::class);
    }

    public function partnerProduct(): BelongsTo
    {
        return $this->belongsTo(PartnerProduct::class);
    }

    public function preHarvestCheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pre_harvest_checked_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FarmingLog::class)->orderByDesc('activity_date');
    }

    public function pendingQuarantineLogs(): HasMany
    {
        return $this->hasMany(FarmingLog::class)
            ->where('activity_type', 'pesticide')
            ->whereNotNull('safe_harvest_date')
            ->whereDate('safe_harvest_date', '>', now()->toDateString());
    }

    public function isReadyForHarvest(): bool
    {
        return !$this->pendingQuarantineLogs()->exists();
    }
}
