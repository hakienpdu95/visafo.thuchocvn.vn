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

    public const ACTIVITY_TYPES = [
        'land_prep'   => ['label' => 'Làm đất',             'badge' => 'badge-neutral', 'btn' => 'btn-neutral'],
        'planting'    => ['label' => 'Gieo hạt/Trồng',      'badge' => 'badge-accent',  'btn' => 'btn-accent'],
        'water'       => ['label' => 'Tưới nước',           'badge' => 'badge-info',    'btn' => 'btn-info'],
        'fertilizer'  => ['label' => 'Bón phân',            'badge' => 'badge-success', 'btn' => 'btn-warning'],
        'pesticide'   => ['label' => 'Phòng trừ sâu bệnh',  'badge' => 'badge-warning', 'btn' => 'btn-error'],
        'harvest'     => ['label' => 'Thu hoạch',           'badge' => 'badge-primary', 'btn' => 'btn-success'],
        'other'       => ['label' => 'Khác',                'badge' => 'badge-ghost',   'btn' => 'btn-neutral'],
        'cultivation' => ['label' => 'Canh tác',            'badge' => 'badge-neutral', 'btn' => 'btn-neutral'],
    ];

    public const MATERIAL_TYPES = ['fertilizer', 'pesticide'];

    protected $fillable = [
        'farming_batch_id',
        'activity_type',
        'activity_date',
        'agri_fertilizer_id',
        'agri_pesticide_id',
        'vendor_farming_step_id',
        'material_name',
        'dosage',
        'isolation_days',
        'harvest_yield',
        'buyer_name',
        'operator_name',
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
            'isolation_days'    => 'integer',
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

    public function typeLabel(): string
    {
        return $this->vendorFarmingStep?->step_name
            ?? self::ACTIVITY_TYPES[$this->activity_type]['label']
            ?? $this->activity_type;
    }

    public function materialText(): ?string
    {
        return $this->material_name
            ?? $this->agriPesticide?->trade_name
            ?? $this->agriFertilizer?->name;
    }

    public function dosageText(): ?string
    {
        if ($this->dosage) {
            return $this->dosage;
        }

        return $this->activity_type !== 'harvest' && $this->quantity
            ? trim((float) $this->quantity . ' ' . $this->unit)
            : null;
    }

    public function harvestYieldText(): ?string
    {
        if ($this->harvest_yield) {
            return $this->harvest_yield;
        }

        return $this->activity_type === 'harvest' && $this->quantity
            ? trim((float) $this->quantity . ' ' . $this->unit)
            : null;
    }

    public function operatorText(): ?string
    {
        return $this->operator_name ?? $this->creator?->name;
    }
}
