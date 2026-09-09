<?php

namespace Modules\Employee\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Models\Employee;

class DestroyEmployeeAction
{
    use AsAction;

    public function handle(Employee $employee): void
    {
        $employee->departments()->detach();
        $employee->delete();
    }
}
