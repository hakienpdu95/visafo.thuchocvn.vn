<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Tạo 8 users mẫu — một user cho mỗi role. Password mặc định: password
 *
 * Email test:
 *   ceo@demo.test       → CEO
 *   sales@demo.test     → Sales
 *   ops@demo.test       → Ops
 *   marketing@demo.test → Marketing
 *   hr@demo.test        → HR
 *   ai_op@demo.test     → AI Operator
 *   admin@demo.test     → System Admin
 *   viewer@demo.test    → Viewer
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['name' => 'CEO User',       'email' => 'ceo@demo.test',       'role' => RoleEnum::CEO],
            ['name' => 'Sales User',     'email' => 'sales@demo.test',     'role' => RoleEnum::SALES],
            ['name' => 'Ops User',       'email' => 'ops@demo.test',       'role' => RoleEnum::OPS],
            ['name' => 'Marketing User', 'email' => 'marketing@demo.test', 'role' => RoleEnum::MARKETING],
            ['name' => 'HR User',        'email' => 'hr@demo.test',        'role' => RoleEnum::HR],
            ['name' => 'AI Operator',    'email' => 'ai_op@demo.test',     'role' => RoleEnum::AI_OP],
            ['name' => 'System Admin',   'email' => 'admin@demo.test',     'role' => RoleEnum::ADMIN],
            ['name' => 'Viewer User',    'email' => 'viewer@demo.test',    'role' => RoleEnum::VIEWER],
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
