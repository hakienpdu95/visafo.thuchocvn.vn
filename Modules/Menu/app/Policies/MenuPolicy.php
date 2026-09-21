<?php

namespace Modules\Menu\Policies;

use App\Models\User;
use Modules\Menu\Models\Menu;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('menu.view');
    }

    public function view(User $user, Menu $menu): bool
    {
        return $user->can('menu.view');
    }

    public function create(User $user): bool
    {
        return $user->can('menu.manage');
    }

    public function update(User $user, Menu $menu): bool
    {
        return $user->can('menu.manage');
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $user->can('menu.manage');
    }
}
