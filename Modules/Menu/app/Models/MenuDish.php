<?php

namespace Modules\Menu\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuDish extends TenantAwareModel
{
    protected $fillable = ['menu_id', 'sort_order', 'dish_name', 'main_ingredients', 'servings'];

    protected function casts(): array
    {
        return ['servings' => 'integer'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
