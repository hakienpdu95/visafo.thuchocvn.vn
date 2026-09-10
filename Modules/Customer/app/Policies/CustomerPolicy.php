<?php

namespace Modules\Customer\Policies;

use App\Models\User;
use Modules\Customer\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customer.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customer.view');
    }

    public function create(User $user): bool
    {
        return $user->can('customer.manage');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customer.manage');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customer.manage');
    }
}
