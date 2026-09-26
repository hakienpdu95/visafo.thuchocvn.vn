<?php

namespace Modules\Contract\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Contract\Models\Contract;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'contract');
    }

    public function view(User $user, Contract $contract): bool
    {
        return ModuleAccess::view($user, 'contract', $contract);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'contract');
    }

    public function update(User $user, Contract $contract): bool
    {
        return ModuleAccess::update($user, 'contract', $contract);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return ModuleAccess::delete($user, 'contract', $contract);
    }
}
