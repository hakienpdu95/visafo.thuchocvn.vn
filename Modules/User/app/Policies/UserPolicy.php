<?php

namespace Modules\User\Policies;

use App\Models\User;

class UserPolicy
{
    /** Super-admin bypass handled by Gate::before() in AppServiceProvider. */

    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view') || $actor->can('users.manage');
    }

    public function view(User $actor, User $target): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.manage');
    }

    public function update(User $actor, User $target): bool
    {
        // Prevent self-editing through admin panel — use profile settings instead.
        if ($actor->id === $target->id) {
            return false;
        }

        return $actor->can('users.manage');
    }

    public function delete(User $actor, User $target): bool
    {
        // Cannot delete yourself
        if ($actor->id === $target->id) {
            return false;
        }

        return $actor->can('users.manage');
    }
}
