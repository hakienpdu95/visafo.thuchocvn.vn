<?php

namespace Modules\Employee\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Employee\Models\Employee;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'employee');
    }

    public function view(User $user, Employee $employee): bool
    {
        return ModuleAccess::view($user, 'employee', $employee);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'employee');
    }

    public function update(User $user, Employee $employee): bool
    {
        return ModuleAccess::update($user, 'employee', $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return ModuleAccess::delete($user, 'employee', $employee);
    }
}
