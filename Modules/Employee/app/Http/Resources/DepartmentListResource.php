<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,

            'is_food_contact'       => (bool) $this->is_food_contact,
            'food_contact_label'    => $this->is_food_contact ? 'Có' : 'Không',
            'food_contact_badge'    => $this->is_food_contact ? 'badge-warning' : 'badge-ghost',

            'employees_count' => (int) $this->employees_count,

            'created_at' => $this->created_at?->format('d/m/Y'),

            'edit_url'   => route('backend.departments.edit', $this->resource),
            'delete_url' => route('backend.departments.destroy', $this->resource),
            'can_delete' => (bool) $request->user()?->can('delete', $this->resource),
        ];
    }
}
