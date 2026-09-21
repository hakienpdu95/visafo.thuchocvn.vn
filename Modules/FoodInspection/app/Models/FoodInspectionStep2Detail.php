<?php

namespace Modules\FoodInspection\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Menu\Enums\MealTime;

class FoodInspectionStep2Detail extends TenantAwareModel
{
    protected $table = 'food_inspection_step2_details';

    protected $fillable = [
        'log_id', 'menu_dish_id', 'line_no', 'meal_time', 'dish_name', 'main_ingredients', 'quantity',
        'prep_time', 'cook_time', 'hygiene_personnel', 'hygiene_equipment', 'hygiene_area', 'sensory_eval', 'action_taken',
    ];

    protected function casts(): array
    {
        return [
            'meal_time'         => MealTime::class,
            'quantity'          => 'integer',
            'hygiene_personnel' => 'boolean',
            'hygiene_equipment' => 'boolean',
            'hygiene_area'      => 'boolean',
            'sensory_eval'      => 'boolean',
        ];
    }

    /** Có tiêu chí vệ sinh hoặc cảm quan không đạt. */
    public function isFailed(): bool
    {
        return ! ($this->hygiene_personnel && $this->hygiene_equipment && $this->hygiene_area && $this->sensory_eval);
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(FoodInspectionStep2Log::class, 'log_id');
    }
}
