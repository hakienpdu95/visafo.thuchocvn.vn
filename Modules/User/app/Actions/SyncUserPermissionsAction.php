<?php

namespace Modules\User\Actions;

use App\Enums\PermissionModule;
use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\PermissionRegistrar;

class SyncUserPermissionsAction
{
    use AsAction;

    public function handle(User $user, string $roleName, array $checked, ?User $actor = null): void
    {
        $all     = PermissionModule::allPermissions();
        $roleRaw = self::rolePermissionNames($roleName);
        $base    = array_values(array_intersect(PermissionModule::expandLegacy($roleRaw), $all));
        $checked = array_values(array_intersect(array_unique($checked), $all));

        if ($actor !== null && ! self::isAdmin($actor)) {
            $checked = array_values(array_filter(
                $checked,
                fn (string $p) => in_array($p, $base, true) || $actor->can($p),
            ));
        }

        $revoked = array_values(array_diff($base, $checked));

        foreach (PermissionModule::cases() as $module) {
            $legacyView   = $module->value . '.view';
            $legacyManage = $module->value . '.manage';

            if (in_array($module->permission('view_all'), $revoked, true) && in_array($legacyView, $roleRaw, true)) {
                $revoked[] = $legacyView;
            }

            $writeRevoked = array_intersect(
                array_map(fn ($a) => $module->permission($a), ['create', 'update', 'delete']),
                $revoked,
            );
            if ($writeRevoked && in_array($legacyManage, $roleRaw, true)) {
                $revoked[] = $legacyManage;
            }
        }

        $direct = array_values(array_diff($checked, $roleRaw));
        foreach ($direct as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user->syncPermissions($direct);
        $user->forceFill(['revoked_permissions' => $revoked ?: null])->save();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function roleDefaults(string $roleName): array
    {
        return array_values(array_intersect(
            PermissionModule::expandLegacy(self::rolePermissionNames($roleName)),
            PermissionModule::allPermissions(),
        ));
    }

    public static function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
    }

    private static function rolePermissionNames(string $roleName): array
    {
        $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->with('permissions')->first();

        return $role ? $role->permissions->pluck('name')->all() : [];
    }
}
