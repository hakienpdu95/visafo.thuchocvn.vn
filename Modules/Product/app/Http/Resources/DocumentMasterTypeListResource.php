<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentMasterTypeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $category = $this->applicable_category;

        return [
            'id'   => $this->id,
            'code' => $this->code,
            'name' => $this->name,

            'applicable_category_value' => $category->value,
            'applicable_category_label' => $category->label(),

            'is_required_issue_date'  => $this->is_required_issue_date,
            'is_required_expiry_date' => $this->is_required_expiry_date,
            'default_validity_months' => $this->default_validity_months,

            'edit_url'   => route('backend.document-master-types.edit', $this->resource),
            'delete_url' => route('backend.document-master-types.destroy', $this->resource),
            'can_delete' => true,
        ];
    }
}
