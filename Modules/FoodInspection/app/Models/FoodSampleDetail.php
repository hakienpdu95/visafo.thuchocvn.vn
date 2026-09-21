<?php

namespace Modules\FoodInspection\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Menu\Enums\MealTime;

class FoodSampleDetail extends TenantAwareModel
{
    /** Quy định: hủy mẫu chỉ được thực hiện sau ít nhất 24 giờ kể từ lúc lấy mẫu. */
    public const MIN_RETENTION_HOURS = 24;

    protected $table = 'food_sample_details';

    protected $fillable = [
        'log_id', 'step3_detail_id', 'menu_dish_id', 'line_no', 'meal_time', 'dish_name', 'portion_qty',
        'sample_volume', 'container_type', 'storage_temp', 'sampled_at', 'sampler_name',
        'destroyed_at', 'destroyer_name', 'quality_note',
    ];

    protected function casts(): array
    {
        return [
            'meal_time'    => MealTime::class,
            'portion_qty'  => 'integer',
            'storage_temp' => 'decimal:1',
            'sampled_at'   => 'datetime',
            'destroyed_at' => 'datetime',
        ];
    }

    public function isDestroyed(): bool
    {
        return $this->destroyed_at !== null;
    }

    /** Mốc sớm nhất được phép hủy mẫu. */
    public function destroyableFrom(): Carbon
    {
        return $this->sampled_at->copy()->addHours(self::MIN_RETENTION_HOURS);
    }

    public function isDestroyable(?Carbon $now = null): bool
    {
        return ! $this->isDestroyed() && ($now ?? now())->greaterThanOrEqualTo($this->destroyableFrom());
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(FoodSampleLog::class, 'log_id');
    }
}
