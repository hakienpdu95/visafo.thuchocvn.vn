<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum as P;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createAllPermissions();
        $this->createRolesWithPermissions();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    // ── Tạo toàn bộ permissions từ PermissionEnum ─────────────────────

    private function createAllPermissions(): void
    {
        foreach (P::cases() as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission->value,
                'guard_name' => 'web',
            ]);
        }
    }

    // ── Map role → danh sách permission values ────────────────────────

    private function createRolesWithPermissions(): void
    {
        foreach ($this->rolePermissionMap() as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }
    }

    // ── Bảng phân quyền đầy đủ theo File 03 User Role Analysis ────────
    //
    // Sau cleanup/remove-non-competency-modules (xóa 26 module CRM/HR-ops/
    // Consulting OS): mọi permission chỉ gate route/controller của module đã
    // xóa (Lead/Customer/Task/Sop/WorkflowAutomation/Report/JobPosting/
    // Recruitment/Marketplace/Subscription(trừ view)/SolutionCatalog/
    // BusinessBlueprint/OrganizationSolution/Deployment/BusinessProject/
    // DecisionLog) đã bị gỡ khỏi PermissionEnum — xem PermissionEnum.php.
    // Role BCOS (LEAD_CONSULTANT/CONSULTANT/BUSINESS_ANALYST/PROJECT_MANAGER/
    // CUSTOMER_SUCCESS) không còn permission nào để gán, không seed nữa.

    private function rolePermissionMap(): array
    {
        return [

            // ─────────────────────────────────────────────────────────
            // CEO / Founder — Full visibility
            // ─────────────────────────────────────────────────────────
            RoleEnum::CEO->value => [
                P::CEO_DASH_FULL->value,

                P::SALES_AI_VIEW->value,

                P::PROMPT_VIEW->value,

                P::AI_LOGS_VIEW->value,

                P::AI_COPILOT_USE->value,
                P::AI_COPILOT_VIEW_USAGE->value,

                P::USERS_VIEW->value,

                P::AUDIT_VIEW->value,

                P::VENDOR_VIEW->value,

                P::PRODUCT_VIEW->value,

                P::WAREHOUSE_VIEW->value,

                P::RECALL_VIEW->value,

                P::COMPLIANCE_VIEW->value,

                P::EMPLOYEE_VIEW->value,

                // Export dữ liệu nhạy cảm: Full — CEO phê duyệt export (SoD chặn tự duyệt request của mình)
                P::EXPORT_REQUEST_VIEW->value,
                P::EXPORT_REQUEST_APPROVE->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Sales Team — AI assist
            // ─────────────────────────────────────────────────────────
            RoleEnum::SALES->value => [
                P::SALES_AI_USE->value,

                P::AI_COPILOT_USE->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Operations
            // ─────────────────────────────────────────────────────────
            RoleEnum::OPS->value => [
                P::CEO_DASH_VIEW->value,

                P::AI_LOGS_VIEW->value,

                P::VENDOR_VIEW->value,
                P::VENDOR_MANAGE->value,

                P::PRODUCT_VIEW->value,
                P::PRODUCT_MANAGE->value,

                P::WAREHOUSE_VIEW->value,
                P::WAREHOUSE_MANAGE->value,

                P::RECALL_VIEW->value,
                P::RECALL_MANAGE->value,

                P::COMPLIANCE_VIEW->value,
                P::COMPLIANCE_MANAGE->value,

                P::EMPLOYEE_VIEW->value,
                P::EMPLOYEE_MANAGE->value,

                // Export dữ liệu nhạy cảm: view (minh bạch nội bộ) — KHÔNG approve
                P::EXPORT_REQUEST_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Marketing
            // ─────────────────────────────────────────────────────────
            RoleEnum::MARKETING->value => [
                P::SALES_AI_VIEW->value,

                // Export dữ liệu nhạy cảm: view (minh bạch nội bộ)
                P::EXPORT_REQUEST_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // HR / Admin Staff
            // ─────────────────────────────────────────────────────────
            RoleEnum::HR->value => [
                P::USERS_HR->value,

                P::EMPLOYEE_VIEW->value,
                P::EMPLOYEE_MANAGE->value,

                // Export dữ liệu nhạy cảm: view (minh bạch nội bộ)
                P::EXPORT_REQUEST_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // AI Operator — Prompt management, AI logs
            // ─────────────────────────────────────────────────────────
            RoleEnum::AI_OP->value => [
                P::CEO_DASH_VIEW->value,

                P::SALES_AI_CONFIG_PROMPT->value,

                P::PROMPT_FULL->value,

                P::AI_LOGS_FULL->value,

                // Export dữ liệu nhạy cảm: view (minh bạch nội bộ)
                P::EXPORT_REQUEST_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // System Admin — Full config access, no business data access
            // ─────────────────────────────────────────────────────────
            RoleEnum::ADMIN->value => [
                P::CEO_DASH_VIEW->value,
                P::CEO_DASH_CONFIG->value,

                P::SALES_AI_CONFIG->value,

                P::PROMPT_ADMIN_CONFIG->value,

                P::AI_LOGS_FULL->value,

                P::USERS_MANAGE->value,
                P::ROLES_MANAGE->value,

                P::INTEGRATION_MANAGE->value,
                P::AUDIT_VIEW->value,
                P::SYSTEM_CONFIG->value,

                P::VENDOR_VIEW->value,
                P::VENDOR_MANAGE->value,

                P::PRODUCT_VIEW->value,
                P::PRODUCT_MANAGE->value,

                P::WAREHOUSE_VIEW->value,
                P::WAREHOUSE_MANAGE->value,

                P::RECALL_VIEW->value,
                P::RECALL_MANAGE->value,

                P::COMPLIANCE_VIEW->value,
                P::COMPLIANCE_MANAGE->value,

                P::EMPLOYEE_VIEW->value,
                P::EMPLOYEE_MANAGE->value,

                // Export dữ liệu nhạy cảm: Full — System Admin phê duyệt export (SoD chặn tự duyệt request của mình)
                P::EXPORT_REQUEST_VIEW->value,
                P::EXPORT_REQUEST_APPROVE->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Viewer / Partner — Read-only
            // ─────────────────────────────────────────────────────────
            RoleEnum::VIEWER->value => [
                P::CEO_DASH_VIEW->value,

                // Export dữ liệu nhạy cảm: view (minh bạch nội bộ)
                P::EXPORT_REQUEST_VIEW->value,
            ],
        ];
    }
}
