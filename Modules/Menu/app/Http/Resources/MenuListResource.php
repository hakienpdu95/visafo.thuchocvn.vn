<?php

namespace Modules\Menu\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'             => $this->id,
            'menu_date'      => $this->menu_date?->format('d/m/Y'),
            'meal_value'     => $this->meal_time->value,
            'meal_label'     => $this->meal_time->label(),
            'customer_name'  => $this->customer?->name,
            'dishes_count'   => (int) $this->dishes_count,
            'total_servings' => (int) $this->total_servings,
            'note'           => $this->note,
            'edit_url'       => route('backend.menus.edit', $this->resource),
            'delete_url'     => route('backend.menus.destroy', $this->resource),
            'can_update'     => (bool) $user?->can('update', $this->resource),
            'can_delete'     => (bool) $user?->can('delete', $this->resource),
        ];
    }
}
