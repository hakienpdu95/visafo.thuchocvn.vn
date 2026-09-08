<?php

namespace App\Shared\Tenancy\Enums;

/**
 * Lifecycle đầy đủ theo SRS01-FR-ORG-003 (GAP_ANALYSIS_v1.0.md §3.3 ORG-01):
 * Draft -> PendingApproval -> Active -> Suspended -> Archived.
 * `Inactive` giữ lại (không xoá) để tương thích dữ liệu cũ trước khi có lifecycle
 * này — coi như alias của Suspended, không dùng cho record mới.
 */
enum OrganizationStatus: string
{
    case Draft           = 'draft';
    case PendingApproval = 'pending_approval';
    case Active          = 'active';
    case Suspended       = 'suspended';
    case Archived        = 'archived';
    case Inactive        = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::Draft           => 'Bản nháp',
            self::PendingApproval => 'Chờ phê duyệt',
            self::Active          => 'Đang hoạt động',
            self::Suspended       => 'Tạm khóa',
            self::Archived        => 'Đã lưu trữ',
            self::Inactive        => 'Không hoạt động',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft           => 'badge-ghost',
            self::PendingApproval => 'badge-warning',
            self::Active          => 'badge-success',
            self::Suspended       => 'badge-error',
            self::Archived        => 'badge-neutral',
            self::Inactive        => 'badge-ghost',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /** SRS01-BR-002-ish: transition hợp lệ — không cho nhảy tuỳ ý giữa các trạng thái. */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft           => in_array($target, [self::PendingApproval, self::Archived], true),
            self::PendingApproval => in_array($target, [self::Active, self::Draft], true),
            self::Active          => in_array($target, [self::Suspended, self::Archived], true),
            self::Suspended       => in_array($target, [self::Active, self::Archived], true),
            self::Archived        => false,
            self::Inactive        => in_array($target, [self::Active, self::Archived], true),
        };
    }
}
