<?php

namespace Modules\Auth\Actions;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Auth\Data\RegisterOrganizationData;

class RegisterOrganizationAction
{
    use AsAction;

    public function handle(RegisterOrganizationData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name'     => $data->name,
                'email'    => $data->email,
                'password' => Hash::make($data->password),
            ]);

            $user->assignRole(RoleEnum::VIEWER->value);

            ActivityLogger::info('Auth', 'user_registered', $user, [
                'email' => $user->email,
            ]);

            return $user;
        });
    }
}
