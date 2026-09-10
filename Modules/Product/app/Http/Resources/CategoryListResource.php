<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'name'        => $this->name,
            'description' => $this->description,

            'is_active'    => (bool) $this->is_active,
            'status_label' => $this->is_active ? 'Đang dùng' : 'Ngừng dùng',
            'status_badge' => $this->is_active ? 'badge-success' : 'badge-ghost',

            'created_at' => $this->created_at?->format('d/m/Y'),

            'edit_url'   => route('backend.categories.edit', $this->resource),
            'delete_url' => route('backend.categories.destroy', $this->resource),
            'can_delete' => (bool) $request->user()?->can('delete', $this->resource),
        ];
    }
}
