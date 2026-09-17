<?php

namespace Modules\SalesPackage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SalesPackage\Enums\SalesPackageStatus;

class SalesPackageListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status  = $this->status;
        $isDraft = $status === SalesPackageStatus::Draft;

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'version'         => $this->version,
            'customer_name'   => $this->customer?->name,
            'readiness_score' => $this->readiness_score,
            'items_count'     => $this->items_count,

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'created_at' => $this->created_at?->format('d/m/Y H:i'),

            'show_url'   => route('backend.sales-packages.show', $this->resource),
            'edit_url'   => route('backend.sales-packages.edit', $this->resource),
            'export_url' => route('backend.sales-packages.export', $this->resource),
            'delete_url' => route('backend.sales-packages.destroy', $this->resource),
            'can_export' => $this->items_count > 0,
            'can_edit'   => $isDraft,
            'can_delete' => $isDraft,
        ];
    }
}
