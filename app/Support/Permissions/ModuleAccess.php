<?php

namespace App\Support\Permissions;

use App\Models\User;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Model;

class ModuleAccess
{
    public static function viewAny(User $user, string $module): bool
    {
        return $user->can("$module.view_all") || $user->can("$module.view_own");
    }

    public static function onlyOwn(User $user, string $module): bool
    {
        return ! $user->can("$module.view_all") && $user->can("$module.view_own");
    }

    public static function view(User $user, string $module, ?Model $record = null): bool
    {
        if ($user->can("$module.view_all")) {
            return true;
        }

        if (! $user->can("$module.view_own")) {
            return false;
        }

        return $record === null || ! self::isOwnable($record) || $record->isCreatedBy($user);
    }

    public static function create(User $user, string $module): bool
    {
        return $user->can("$module.create");
    }

    public static function update(User $user, string $module, ?Model $record = null): bool
    {
        return $user->can("$module.update") && self::view($user, $module, $record);
    }

    public static function delete(User $user, string $module, ?Model $record = null): bool
    {
        return $user->can("$module.delete") && self::view($user, $module, $record);
    }

    private static function isOwnable(Model $record): bool
    {
        return in_array(HasCreator::class, class_uses_recursive($record), true);
    }
}
