<?php

namespace Modules\Menu\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Menu\Models\Menu;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'menu');
    }

    public function view(User $user, Menu $menu): bool
    {
        return ModuleAccess::view($user, 'menu', $menu);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'menu');
    }

    public function update(User $user, Menu $menu): bool
    {
        return ModuleAccess::update($user, 'menu', $menu);
    }

    public function delete(User $user, Menu $menu): bool
    {
        return ModuleAccess::delete($user, 'menu', $menu);
    }
}
