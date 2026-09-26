<?php

namespace Modules\Menu\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;
use Modules\Menu\Enums\MealTime;

/** Thực đơn của một cơ sở (khách hàng) theo ngày + bữa ăn. */
class Menu extends TenantAwareModel
{
    use HasCreator;

    protected $fillable = ['customer_id', 'menu_date', 'meal_time', 'note'];

    protected function casts(): array
    {
        return [
            'menu_date' => 'date',
            'meal_time' => MealTime::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(MenuDish::class)->orderBy('sort_order')->orderBy('id');
    }

    public function permissionModule(): string
    {
        return 'menu';
    }
}
