<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DepartmentEmployee extends Pivot
{
    use HasUlids;

    protected $table = 'department_employee';
}
