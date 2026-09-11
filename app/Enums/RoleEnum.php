<?php
namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN            = 'system_admin';
    case DIRECTOR         = 'director';
    case QA_QC_MANAGER    = 'qa_qc_manager';
    case PURCHASING_STAFF = 'purchasing_staff';
    case SALES_STAFF      = 'sales_staff';
    case FARMER           = 'farmer';

    public function label(): string
    {
        return match($this) {
            self::ADMIN            => 'Quản trị hệ thống',
            self::DIRECTOR         => 'Ban Giám đốc',
            self::QA_QC_MANAGER    => 'Quản lý Chất lượng / ATTP',
            self::PURCHASING_STAFF => 'Nhân viên Cung ứng',
            self::SALES_STAFF      => 'Nhân viên Kinh doanh',
            self::FARMER           => 'Nông hộ',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::ADMIN            => 'badge-error',
            self::DIRECTOR         => 'badge-primary',
            self::QA_QC_MANAGER    => 'badge-warning',
            self::PURCHASING_STAFF => 'badge-info',
            self::SALES_STAFF      => 'badge-success',
            self::FARMER           => 'badge-neutral',
        };
    }

    /** Danh sách khoá module hiển thị trên sidebar/dashboard cho vai trò này. */
    public function visibleModules(): array
    {
        return match($this) {
            self::ADMIN            => ['dashboard', 'products', 'vendors', 'customers', 'contracts', 'compliance', 'traceability', 'employees', 'users'],
            self::DIRECTOR         => ['dashboard', 'products', 'vendors', 'customers', 'contracts', 'compliance', 'traceability', 'employees', 'users'],
            self::QA_QC_MANAGER    => ['dashboard', 'products', 'vendors', 'customers', 'compliance', 'traceability'],
            self::PURCHASING_STAFF => ['dashboard', 'products', 'vendors', 'contracts'],
            self::SALES_STAFF      => ['dashboard', 'customers', 'contracts', 'traceability'],
            self::FARMER           => [],
        };
    }
}
