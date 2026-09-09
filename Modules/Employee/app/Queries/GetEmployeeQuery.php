<?php

namespace Modules\Employee\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Employee\Models\Employee;

class GetEmployeeQuery implements QueryInterface
{
    public function __construct(
        public readonly Employee $employee,
    ) {}
}
