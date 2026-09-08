<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ActivityLog\Database\Seeders\ActivityLogPermissionsSeeder;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationRolePermissionSeeder;
use Modules\Product\Database\Seeders\DocumentMasterTypeSeeder;

/**
 * Master Seeder — điểm khởi chạy duy nhất cho toàn bộ dữ liệu mặc định hệ thống.
 *
 * Lệnh chạy:
 *   php artisan db:seed
 *   php artisan db:seed --class=Database\\Seeders\\SystemDataSeeder
 *
 * Không bao gồm:
 *   - OrganizationDemoSeeder (1000 orgs demo — chỉ chạy thủ công khi cần)
 *   - Các seeder rỗng (Employee, Customer, Branch, Department, Project...)
 *   - Subscription/Lead/LeadPipelineStage/LeadSource/Recruitment/JobPosting/Deployment/
 *     BusinessSolution/BusinessBlueprint/OrganizationSolution/BusinessProject/Survey/
 *     VerticalTemplate/Sandbox/Certifications/CareerPathway/AiImpact/Passport/Campaigns/
 *     Employee/Department/Branch/Position/JobTitle/Person(persons,invitations,imports)/
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

            // ── 4. Template roles cấp org (owner/admin/manager/member) ────
            OrganizationRolePermissionSeeder::class,

            // ── 5. Org hệ thống mặc định (id=1 trên fresh DB) ────────────
            SystemOrganizationSeeder::class,

            // ── 6. Demo organization (dev/test) ───────────────────────────
            OrganizationSeeder::class,

            // ── 7. Test users (1 per role) ────────────────────────────────
            UserSeeder::class,

            // ── 8. Từ điển loại giấy tờ pháp lý (document_master_types) ──
            DocumentMasterTypeSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('  ✓ Tất cả dữ liệu mặc định đã được seed thành công.');
        $this->command->newLine();
    }
}
