<?php

namespace Modules\Employee\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Data\Requests\StoreDepartmentData;
use Modules\Employee\Models\Department;

class StoreDepartmentAction
{
    use AsAction;

    public function handle(StoreDepartmentData $data): Department
    {
        return Department::create([
            'name'            => $data->name,
            'is_food_contact' => $data->is_food_contact,
        ]);
    }
}
