<?php

namespace Modules\LabelTemplate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabelTemplateListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'view_path'    => $this->view_path,
            'description'  => $this->description,
            'default_size' => $this->default_size,
            'created_at'   => $this->created_at?->format('d/m/Y'),

            'preview_url' => route('backend.label-templates.preview', $this->resource),
            'edit_url'    => route('backend.label-templates.edit', $this->resource),
            'delete_url'  => route('backend.label-templates.destroy', $this->resource),
            'can_update'  => auth()->user()?->can('update', $this->resource) ?? false,
            'can_delete'  => auth()->user()?->can('delete', $this->resource) ?? false,
        ];
    }
}
