<?php

namespace App\Enums;

enum PermissionModule: string
{
    case PRODUCT         = 'product';
    case VENDOR          = 'vendor';
    case GOODS_RECEIPT   = 'goods_receipt';
    case FOOD_INSPECTION = 'food_inspection';
    case MENU            = 'menu';
    case SALES_ORDER     = 'sales_order';
    case LABEL_TEMPLATE  = 'label_template';
    case TRACE_LOG       = 'trace_log';
    case REPORT          = 'report';
    case CUSTOMER        = 'customer';
    case CONTRACT        = 'contract';
    case COMPLIANCE      = 'compliance';
    case TRACEABILITY    = 'traceability';
    case EMPLOYEE        = 'employee';
    case USERS           = 'users';

    public const ACTIONS = ['view_all', 'view_own', 'create', 'update', 'delete'];

    public const ACTION_LABELS = [
        'view_all' => 'Xem (Tất cả)',
        'view_own' => 'Xem (Dữ liệu tự tạo)',
        'create'   => 'Thêm mới',
        'update'   => 'Sửa',
        'delete'   => 'Xóa',
    ];

    public function label(): string
    {
        return match ($this) {
            self::PRODUCT         => 'Sản phẩm & Danh mục',
            self::VENDOR          => 'Nhà cung cấp',
            self::GOODS_RECEIPT   => 'Nhập hàng & Lô hàng',
            self::FOOD_INSPECTION => 'Sổ kiểm thực',
            self::MENU            => 'Thực đơn',
            self::SALES_ORDER     => 'Đơn xuất hàng',
            self::LABEL_TEMPLATE  => 'Mẫu tem in',
            self::TRACE_LOG       => 'Nhật ký TXNG',
            self::REPORT          => 'Thống kê - Báo cáo',
            self::CUSTOMER        => 'Khách hàng & Gói hồ sơ',
            self::CONTRACT        => 'Hợp đồng',
            self::COMPLIANCE      => 'Kho tài liệu & Vùng trồng',
            self::TRACEABILITY    => 'Truy xuất nguồn gốc',
            self::EMPLOYEE        => 'Nhân sự',
            self::USERS           => 'Tài khoản người dùng',
        };
    }

    public function actions(): array
    {
        return match ($this) {
            self::REPORT, self::TRACEABILITY => ['view_all'],
            self::TRACE_LOG                  => ['view_all', 'view_own', 'update'],
            default                          => self::ACTIONS,
        };
    }

    public function permission(string $action): string
    {
        return $this->value . '.' . $action;
    }

    public function permissions(): array
    {
        return array_map(fn (string $a) => $this->permission($a), $this->actions());
    }

    public static function allPermissions(): array
    {
        return array_merge(...array_map(fn (self $m) => $m->permissions(), self::cases()));
    }

    public static function expandLegacy(array $names): array
    {
        $expanded = $names;

        foreach (self::cases() as $module) {
            if (in_array($module->value . '.view', $names, true)) {
                $expanded[] = $module->permission('view_all');
            }
            if (in_array($module->value . '.manage', $names, true)) {
                foreach (array_intersect(['create', 'update', 'delete'], $module->actions()) as $action) {
                    $expanded[] = $module->permission($action);
                }
            }
        }

        return array_values(array_unique($expanded));
    }
}
