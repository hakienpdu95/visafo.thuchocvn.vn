<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\User\Data\StoreUserData;
use Modules\User\Events\UserCreated;
use Modules\User\Events\UserRoleAssigned;
use Modules\User\Notifications\WelcomeUserNotification;
use Spatie\Permission\PermissionRegistrar;

class StoreUserAction
{
    use AsAction;

    public function handle(StoreUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name'       => $data->name,
                'email'      => $data->email,
                'password'   => Hash::make($data->password),
                'department' => $data->department,
                'vendor_id'  => $data->vendor_id,
                'is_active'  => $data->is_active,
            ]);

            $user->assignRole($data->system_role);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            event(new UserCreated($user));
            event(new UserRoleAssigned($user, $data->system_role));

            if ($data->send_welcome_email) {
                $user->notify(new WelcomeUserNotification($data->password, $data->system_role));
            }

            if ($this->isGmailAddress($user->email)) {
                $user->sendEmailVerificationNotification();
            }

            return $user;
        });
    }

    private function isGmailAddress(string $email): bool
    {
        return Str::of($email)->lower()->afterLast('@')->is('gmail.com');
    }
}
