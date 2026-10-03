<?php

namespace Modules\SalesOrder\Enums;

/** Trạng thái của một tem truy xuất (một PrintLog). */
enum PrintLogStatus: string
{
    case Active   = 'active';
    case Recalled = 'recalled';
    case Error    = 'error';
    /** Đã hủy để cấp lại mã mới (tem mất / hỏng) — chỉ đặt qua thao tác "Hủy mã & Cấp lại", không phục hồi được. */
    case Revoked  = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Đang lưu hành',
            self::Recalled => 'Đã thu hồi',
            self::Error    => 'Tem lỗi',
            self::Revoked  => 'Đã hủy (cấp lại)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active   => 'badge-success',
            self::Recalled => 'badge-error',
            self::Error    => 'badge-warning',
            self::Revoked  => 'badge-ghost',
        };
    }

    /** Tem chưa bị thu hồi/đánh dấu lỗi. */
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
