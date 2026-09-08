<?php

namespace Modules\Compliance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceWarningListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $severity = $this->severity;
        $category = $this->category;
        $status   = $this->status;

        return [
            'id' => $this->id,

            'severity_value' => $severity->value,
            'severity_label' => $severity->label(),
            'severity_badge' => $severity->badgeClass(),

            'category_label' => $category->label(),

            'title'   => $this->title,
            'message' => $this->message,

            'due_date'       => $this->due_date?->format('d/m/Y'),
            'days_remaining' => $this->daysRemaining(),

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'acknowledge_url' => route('backend.compliance-warnings.acknowledge', $this->resource),
            'resolve_url'     => route('backend.compliance-warnings.resolve', $this->resource),

            'can_acknowledge' => $status->value === 'pending',
            'can_resolve'     => $status->value !== 'resolved',
        ];
    }
}
