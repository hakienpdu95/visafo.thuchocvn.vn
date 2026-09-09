<?php

namespace Modules\Employee\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Employee\Models\Employee;

class GetEmployeeHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Employee
    {
        $employee = $query->employee;

        $employee->load([
            'departments',
            'healthRecords' => fn ($q) => $q->orderByDesc('issue_date'),
        ]);

        return $employee;
    }
}
