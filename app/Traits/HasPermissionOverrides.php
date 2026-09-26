<?php

namespace App\Traits;

use App\Enums\PermissionModule;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\PermissionRegistrar;

trait HasPermissionOverrides
{
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        $name = $this->permissionName($permission);

        if ($name === null) {
            return $this->spatieHasPermissionTo($permission, $guardName);
        }

        foreach ($this->permissionAliases($name) as $candidate) {
            if ($this->hasRawPermission($candidate, $guardName)) {
                return true;
            }
        }

        return false;
    }

    public function getAllPermissions(): Collection
    {
        $revoked = $this->revokedPermissions();

        return $this->spatieGetAllPermissions()
            ->reject(fn ($p) => in_array($p->name, $revoked, true))
            ->values();
    }

    public function revokedPermissions(): array
    {
        if (! array_key_exists('revoked_permissions', $this->attributes)) {
            return [];
        }

        return (array) ($this->getAttribute('revoked_permissions') ?? []);
    }

    public function effectivePermissionNames(): array
    {
        return array_values(array_filter(
            PermissionModule::allPermissions(),
            fn (string $p) => $this->hasPermissionTo($p),
        ));
    }

    private function hasRawPermission(string $name, ?string $guardName): bool
    {
        if (in_array($name, $this->revokedPermissions(), true)) {
            return false;
        }

        try {
            return $this->spatieHasPermissionTo($name, $guardName);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function permissionAliases(string $name): array
    {
        if (! str_contains($name, '.')) {
            return [$name];
        }

        [$module, $action] = explode('.', $name, 2);

        return match ($action) {
            'view'                      => [$name, "$module.view_all", "$module.view_own"],
            'manage'                    => [$name, "$module.create", "$module.update", "$module.delete"],
            'view_all'                  => [$name, "$module.view"],
            'create', 'update', 'delete' => [$name, "$module.manage"],
            default                     => [$name],
        };
    }

    private function permissionName(mixed $permission): ?string
    {
        if ($permission instanceof \BackedEnum) {
            $permission = $permission->value;
        }

        if ($permission instanceof Permission) {
            return $permission->name;
        }

        return is_string($permission) && ! ctype_digit($permission) && ! PermissionRegistrar::isUid($permission) ? $permission : null;
    }
}
