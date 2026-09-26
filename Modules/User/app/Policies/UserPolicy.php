<?php

namespace Modules\User\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;

class UserPolicy
{
    /** Super-admin bypass handled by Gate::before() in AppServiceProvider. */

    public function viewAny(User $actor): bool
    {
        return ModuleAccess::viewAny($actor, 'users') || $actor->can('users.manage');
    }

    public function view(User $actor, User $target): bool
    {
        return ModuleAccess::view($actor, 'users', $target);
    }

    public function create(User $actor): bool
    {
        return ModuleAccess::create($actor, 'users');
    }

    public function update(User $actor, User $target): bool
    {
        // Prevent self-editing through admin panel — use profile settings instead.
        if ($actor->id === $target->id) {
            return false;
        }

        return ModuleAccess::update($actor, 'users', $target);
    }

    public function delete(User $actor, User $target): bool
    {
        // Cannot delete yourself
        if ($actor->id === $target->id) {
            return false;
        }

        return ModuleAccess::delete($actor, 'users', $target);
    }
}
