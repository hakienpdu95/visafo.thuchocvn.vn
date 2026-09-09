<?php

namespace Modules\Employee\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Models\Department;

class DestroyDepartmentAction
{
    use AsAction;

    public function handle(Department $department): void
    {
        $department->employees()->detach();
        $department->delete();
    }
}
