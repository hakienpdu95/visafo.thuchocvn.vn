<?php

namespace Modules\Customer\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Customer\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'customer');
    }

    public function view(User $user, Customer $customer): bool
    {
        return ModuleAccess::view($user, 'customer', $customer);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'customer');
    }

    public function update(User $user, Customer $customer): bool
    {
        return ModuleAccess::update($user, 'customer', $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return ModuleAccess::delete($user, 'customer', $customer);
    }
}
