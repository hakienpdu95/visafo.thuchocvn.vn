<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\User\Data\UpdateUserData;
use Modules\User\Events\UserRoleAssigned;
use Spatie\Permission\PermissionRegistrar;

class UpdateUserAction
{
    use AsAction;

    public function handle(User $user, UpdateUserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $previousRole = $user->getRoleNames()->first();

            $updateData = [
                'name'       => $data->name,
                'email'      => $data->email,
                'department' => $data->department,
                'is_active'  => $data->is_active,
            ];

            if (! empty($data->password)) {
                $updateData['password'] = Hash::make($data->password);
            }

            $user->fill($updateData)->save();

            $user->syncRoles([$data->system_role]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            if ($previousRole !== $data->system_role) {
                event(new UserRoleAssigned($user, $data->system_role));
            }

            return $user;
        });
    }
}
