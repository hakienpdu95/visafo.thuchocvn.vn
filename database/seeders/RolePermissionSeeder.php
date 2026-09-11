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

    // ── Ma trận phân quyền — mô hình F&B Traceability Visafo ───────────
    //
    // 5 vai trò cốt lõi, khớp 1:1 với 9 module thực tế của hệ thống:
    // Dashboard, Products, Vendors, Customers, Contracts, Compliance,
    // Traceability, Employees, System (Tài khoản/Nhật ký).

    private function rolePermissionMap(): array
    {
        return [

            // ─────────────────────────────────────────────────────────
            // System Admin — Full quyền toàn hệ thống
            // ─────────────────────────────────────────────────────────
            RoleEnum::ADMIN->value => [
                P::PRODUCT_VIEW->value,
                P::PRODUCT_MANAGE->value,

                P::VENDOR_VIEW->value,
                P::VENDOR_MANAGE->value,

                P::CUSTOMER_VIEW->value,
                P::CUSTOMER_MANAGE->value,

                P::CONTRACT_VIEW->value,
                P::CONTRACT_MANAGE->value,

                P::COMPLIANCE_VIEW->value,
                P::COMPLIANCE_MANAGE->value,

                P::TRACEABILITY_VIEW->value,

                P::EMPLOYEE_VIEW->value,
                P::EMPLOYEE_MANAGE->value,

                P::USERS_VIEW->value,
                P::USERS_MANAGE->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Director (Ban Giám đốc) — View toàn bộ module, không thao tác dữ liệu
            // ─────────────────────────────────────────────────────────
            RoleEnum::DIRECTOR->value => [
                P::PRODUCT_VIEW->value,
                P::VENDOR_VIEW->value,
                P::CUSTOMER_VIEW->value,
                P::CONTRACT_VIEW->value,
                P::COMPLIANCE_VIEW->value,
                P::TRACEABILITY_VIEW->value,
                P::EMPLOYEE_VIEW->value,
                P::USERS_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // QA/QC Manager — Full Compliance/Traceability/Products, View Vendors/Customers
            // ─────────────────────────────────────────────────────────
            RoleEnum::QA_QC_MANAGER->value => [
                P::COMPLIANCE_VIEW->value,
                P::COMPLIANCE_MANAGE->value,

                P::TRACEABILITY_VIEW->value,

                P::PRODUCT_VIEW->value,
                P::PRODUCT_MANAGE->value,

                P::VENDOR_VIEW->value,
                P::CUSTOMER_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Purchasing Staff (Nhân viên Cung ứng) — Full Vendors/Contracts, View Products
            // ─────────────────────────────────────────────────────────
            RoleEnum::PURCHASING_STAFF->value => [
                P::VENDOR_VIEW->value,
                P::VENDOR_MANAGE->value,

                P::CONTRACT_VIEW->value,
                P::CONTRACT_MANAGE->value,

                P::PRODUCT_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Sales Staff (Nhân viên Kinh doanh) — Full Customers, View Traceability/Contracts
            // ─────────────────────────────────────────────────────────
            RoleEnum::SALES_STAFF->value => [
                P::CUSTOMER_VIEW->value,
                P::CUSTOMER_MANAGE->value,

                P::TRACEABILITY_VIEW->value,
                P::CONTRACT_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Farmer (Nông hộ) — không có quyền dashboard nào, chỉ dùng
            // Web App ghi nhật ký riêng ở /farmer/* (role:farmer middleware)
            // ─────────────────────────────────────────────────────────
            RoleEnum::FARMER->value => [],
        ];
    }
}
