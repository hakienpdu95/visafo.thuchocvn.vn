<?php

namespace Modules\FoodInspection\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Menu\Enums\MealTime;

class FoodInspectionStep3Detail extends TenantAwareModel
{
    protected $table = 'food_inspection_step3_details';

    protected $fillable = [
        'log_id', 'step2_detail_id', 'menu_dish_id', 'line_no', 'meal_time', 'dish_name', 'quantity',
        'portion_time', 'eat_time', 'equipment_used', 'sensory_eval', 'action_taken',
    ];

    protected function casts(): array
    {
        return [
            'meal_time'    => MealTime::class,
            'quantity'     => 'integer',
            'sensory_eval' => 'boolean',
        ];
    }

    public function isFailed(): bool
    {
        return ! $this->sensory_eval;
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(FoodInspectionStep3Log::class, 'log_id');
    }
}
