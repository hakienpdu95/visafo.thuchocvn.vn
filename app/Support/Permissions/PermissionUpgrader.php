<?php

namespace App\Support\Permissions;

use App\Enums\PermissionModule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class PermissionUpgrader
{
    public function ensurePermissionsExist(string $guard = 'web'): int
    {
        $created = 0;

        foreach (PermissionModule::allPermissions() as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
            $created += $permission->wasRecentlyCreated ? 1 : 0;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $created;
    }

    /** @return array{roles:int, users:int} */
    public function upgrade(bool $dryRun = false): array
    {
        if (! $dryRun) {
            $this->ensurePermissionsExist();
        }

        $roles = 0;
        foreach (Role::with('permissions')->get() as $role) {
            $missing = $this->missingGranular($role->permissions->pluck('name')->all());
            if ($missing) {
                $roles++;
                if (! $dryRun) {
                    $role->givePermissionTo($missing);
                }
            }
        }

        $users = 0;
        User::query()->whereHas('permissions')->with('permissions')->chunkById(200, function ($chunk) use (&$users, $dryRun) {
            foreach ($chunk as $user) {
                $missing = $this->missingGranular($user->permissions->pluck('name')->all());
                if ($missing) {
                    $users++;
                    if (! $dryRun) {
                        $user->givePermissionTo($missing);
                    }
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ['roles' => $roles, 'users' => $users];
    }

    private function missingGranular(array $names): array
    {
        $valid = PermissionModule::allPermissions();

        return array_values(array_intersect(
            array_diff(PermissionModule::expandLegacy($names), $names),
            $valid,
        ));
    }
}
