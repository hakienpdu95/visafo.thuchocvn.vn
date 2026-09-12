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
            $username    = $data->username ? Str::lower($data->username) : null;
            $hasRealMail = filled($data->email);

            $user = User::create([
                'name'              => $data->name,
                'email'             => $data->email ?: $this->syntheticEmail((string) $username),
                'username'          => $username,
                'password'          => Hash::make($data->password),
                'department'        => $data->department,
                'vendor_id'         => $data->vendor_id,
                'employee_id'       => $data->employee_id,
                'is_active'         => $data->is_active,
                'email_verified_at' => $hasRealMail ? null : now(),
            ]);

            $user->assignRole($data->system_role);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            event(new UserCreated($user));
            event(new UserRoleAssigned($user, $data->system_role));

            if ($data->send_welcome_email && $hasRealMail) {
                $user->notify(new WelcomeUserNotification($data->password, $data->system_role));
            }

            if ($hasRealMail && $this->isGmailAddress($user->email)) {
                $user->sendEmailVerificationNotification();
            }

            return $user;
        });
    }

    private function isGmailAddress(string $email): bool
    {
        return Str::of($email)->lower()->afterLast('@')->is('gmail.com');
    }

    /**
     * Tài khoản liên kết Hồ sơ Nhân viên đăng nhập bằng username, không có
     * email thật — sinh 1 email nội bộ hợp lệ để thoả cột NOT NULL/unique.
     */
    private function syntheticEmail(string $username): string
    {
        return $username . '@staff.internal';
    }
}
