<?php

namespace Modules\Employee\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Data\Requests\UpdateDepartmentData;
use Modules\Employee\Models\Department;

class UpdateDepartmentAction
{
    use AsAction;

    public function handle(Department $department, UpdateDepartmentData $data): Department
    {
        $department->update([
            'name'            => $data->name,
            'is_food_contact' => $data->is_food_contact,
        ]);

        return $department;
    }
}
