<?php

namespace Modules\Employee\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Employee\Models\Department;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'employee');
    }

    public function view(User $user, Department $department): bool
    {
        return ModuleAccess::view($user, 'employee', $department);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'employee');
    }

    public function update(User $user, Department $department): bool
    {
        return ModuleAccess::update($user, 'employee', $department);
    }

    public function delete(User $user, Department $department): bool
    {
        return ModuleAccess::delete($user, 'employee', $department);
    }
}
