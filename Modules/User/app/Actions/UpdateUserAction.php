<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            $username     = $data->username ? Str::lower($data->username) : null;
            $hasRealMail  = filled($data->email);

            $updateData = [
                'name'        => $data->name,
                'email'       => $data->email ?: $this->syntheticEmail((string) $username),
                'username'    => $username,
                'department'  => $data->department,
                'vendor_id'   => $data->vendor_id,
                'employee_id' => $data->employee_id,
                'is_active'   => $data->is_active,
            ];

            if (! $hasRealMail && $user->email_verified_at === null) {
                $updateData['email_verified_at'] = now();
            }

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

    /**
     * Tài khoản liên kết Hồ sơ Nhân viên đăng nhập bằng username, không có
     * email thật — sinh 1 email nội bộ hợp lệ để thoả cột NOT NULL/unique.
     */
    private function syntheticEmail(string $username): string
    {
        return $username . '@staff.internal';
    }
}
