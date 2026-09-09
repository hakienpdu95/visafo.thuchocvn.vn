<?php

namespace Modules\Contract\Policies;

use App\Models\User;
use Modules\Contract\Models\Contract;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contract.view');
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->can('contract.view');
    }

    public function create(User $user): bool
    {
        return $user->can('contract.manage');
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->can('contract.manage');
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->can('contract.manage');
    }
}
