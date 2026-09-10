<?php

namespace Modules\Auth\Actions;

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

            // Tài khoản tự đăng ký chưa có vai trò — System Admin gán vai trò
            // phù hợp (director/qa_qc_manager/purchasing_staff/sales_staff)
            // sau khi duyệt, tại màn Quản lý tài khoản.

            ActivityLogger::info('Auth', 'user_registered', $user, [
                'email' => $user->email,
            ]);

            return $user;
        });
    }
}
