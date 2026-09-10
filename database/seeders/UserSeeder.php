<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Tạo 5 users mẫu — một user cho mỗi vai trò F&B Traceability. Password mặc định: password
 *
 * Email test:
 *   admin@demo.test      → System Admin
 *   director@demo.test   → Director (Ban Giám đốc)
 *   qaqc@demo.test        → QA/QC Manager
 *   purchasing@demo.test → Purchasing Staff
 *   sales@demo.test      → Sales Staff
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['name' => 'System Admin',      'email' => 'admin@demo.test',      'role' => RoleEnum::ADMIN],
            ['name' => 'Director User',     'email' => 'director@demo.test',   'role' => RoleEnum::DIRECTOR],
            ['name' => 'QA/QC Manager',     'email' => 'qaqc@demo.test',       'role' => RoleEnum::QA_QC_MANAGER],
            ['name' => 'Purchasing Staff',  'email' => 'purchasing@demo.test', 'role' => RoleEnum::PURCHASING_STAFF],
            ['name' => 'Sales Staff',       'email' => 'sales@demo.test',      'role' => RoleEnum::SALES_STAFF],
        ];

        foreach ($definitions as $def) {
            $user = User::firstOrCreate(
                ['email' => $def['email']],
                [
                    'name'              => $def['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$def['role']->value]);
        }
    }
}
