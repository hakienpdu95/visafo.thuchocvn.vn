<?php

namespace Modules\Warehouse\Enums;

enum RetailItemTagStatus: string
{
    case Provisioned      = 'provisioned';
    case Bound            = 'bound';
    case InStock          = 'in_stock';
    case Sold             = 'sold';
    case Transferred      = 'transferred';
    case TransferredActive = 'transferred_active';
    case Damaged          = 'damaged';
    case Recalled         = 'recalled';

    public function label(): string
    {
        return match ($this) {
            self::Provisioned       => 'Đã in — chưa gắn kết',
            self::Bound             => 'Đã gắn kết — chờ lưu hành',
            self::InStock           => 'Còn trên kệ',
            self::Sold              => 'Đã bán',
            self::Transferred       => 'Đã xuất buôn — chờ lưu hành',
            self::TransferredActive => 'Đã xuất buôn — đang lưu hành',
            self::Damaged           => 'Hư hỏng',
            self::Recalled          => 'Đã thu hồi',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Provisioned       => 'badge-ghost',
            self::Bound             => 'badge-warning',
            self::InStock           => 'badge-success',
            self::Sold              => 'badge-info',
            self::Transferred       => 'badge-warning',
            self::TransferredActive => 'badge-success',
            self::Damaged           => 'badge-neutral',
            self::Recalled          => 'badge-error',
        };
    }

    /** Trạng thái mà Cổng truy xuất công khai được phép hiển thị đầy đủ thông tin sản phẩm. */
    public function isMarketReleased(): bool
    {
        return ! in_array($this, [self::Provisioned, self::Bound, self::Transferred], true);
    }
}
