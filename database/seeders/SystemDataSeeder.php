<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ActivityLog\Database\Seeders\ActivityLogPermissionsSeeder;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Employee\Database\Seeders\DepartmentSeeder;
use Modules\Product\Database\Seeders\CategorySeeder;
use Modules\Product\Database\Seeders\DocumentMasterTypeSeeder;
use Modules\Product\Database\Seeders\ProductMasterDataSeeder;

/**
 * Master Seeder — điểm khởi chạy duy nhất cho toàn bộ dữ liệu mặc định hệ thống.
 *
 * Lệnh chạy:
 *   php artisan db:seed
 *   php artisan db:seed --class=Database\\Seeders\\SystemDataSeeder
 *
 * Không bao gồm:
 *   - Các seeder rỗng (Customer, Branch, Project...)
 *   - Subscription/Lead/LeadPipelineStage/LeadSource/Recruitment/JobPosting/Deployment/
 *     BusinessSolution/BusinessBlueprint/OrganizationSolution/BusinessProject/Survey/
 *     VerticalTemplate/Sandbox/Certifications/CareerPathway/AiImpact/Passport/Campaigns/
 *     Branch/Position/JobTitle/Person(persons,invitations,imports)/
 *     AiCopilot/RoleScope/Assessment: đã bị gỡ cùng các module/route đó
 *     (cleanup/remove-non-competency-modules)
 */
class SystemDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->newLine();
        $this->command->info('┌──────────────────────────────────────────┐');
        $this->command->info('│       SystemDataSeeder — starting...     │');
        $this->command->info('└──────────────────────────────────────────┘');
        $this->command->newLine();

        $this->call([
            // ── 1. IAM: 8 tenant roles + 40+ permissions ─────────────────
            RolePermissionSeeder::class,

            // ── 2. Additional module permissions (cần roles tồn tại trước)
            ActivityLogPermissionsSeeder::class,

            // ── 3. Super-admin role + 2 tài khoản hệ thống ───────────────
            AuthDatabaseSeeder::class,

            // ── 4. Test users (1 per role) ────────────────────────────────
            UserSeeder::class,

            // ── 5. Từ điển loại giấy tờ pháp lý (document_master_types) ──
            DocumentMasterTypeSeeder::class,

            // ── 6. 6 nhóm thực phẩm chuẩn ATTP (categories) — Rule Engine ─
            CategorySeeder::class,

            // ── 7. Master data sản phẩm (datafiles/products_master_data.json) ─
            ProductMasterDataSeeder::class,

            DepartmentSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('  ✓ Tất cả dữ liệu mặc định đã được seed thành công.');
        $this->command->newLine();
    }
}
