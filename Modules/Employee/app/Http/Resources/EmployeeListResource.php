<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $healthStatus = $this->healthCheckStatus();
        $attpStatus   = $this->attpTrainingStatus();

        return [
            'id'         => $this->id,
            'avatar_url' => $this->getMediaUrl('avatar', 'thumb'),
            'full_name'  => $this->full_name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'job_title'  => $this->job_title,

            'departments' => $this->departments->map(fn ($d) => [
                'id'   => $d->id,
                'name' => $d->name,
            ])->all(),
            'department_names' => $this->departments->pluck('name')->implode(', '),

            'health_status_value' => $healthStatus->value,
            'health_status_label' => $healthStatus->label(),
            'health_status_class' => $healthStatus->badgeClass(),

            'attp_status_value' => $attpStatus->value,
            'attp_status_label' => $attpStatus->label(),
            'attp_status_class' => $attpStatus->badgeClass(),

            'edit_url'   => route('backend.employees.edit', $this->resource),
            'delete_url' => route('backend.employees.destroy', $this->resource),
        ];
    }
}
