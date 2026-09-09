<?php

namespace Modules\Employee\Policies;

use App\Models\User;
use Modules\Employee\Models\Employee;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employee.manage');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employee.manage');
    }
}
