<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentMasterTypeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $group = $this->document_group;

        return [
            'id'   => $this->id,
            'code' => $this->code,
            'name' => $this->name,

            'document_group_value' => $group?->value,
            'document_group_label' => $group?->label() ?? 'Chưa phân nhóm',

            'is_required_issue_date'  => $this->is_required_issue_date,
            'is_required_expiry_date' => $this->is_required_expiry_date,
            'default_validity_months' => $this->default_validity_months,

            'edit_url'   => route('backend.document-master-types.edit', $this->resource),
            'delete_url' => route('backend.document-master-types.destroy', $this->resource),
            'can_delete' => true,
        ];
    }
}
