<?php

namespace App\Enums;

/**
 * SRS01-FR-ID-003 (GAP_ANALYSIS_v1.0.md §3.3 ID-02): lifecycle CỦA TÀI KHOẢN,
 * tách biệt hoàn toàn khỏi employment status (Employee.status giữ nguyên cho
 * mục đích HR — nhân sự nghỉ việc ≠ tài khoản bị khóa). Cột mới `users.lifecycle_status`,
 * không thay thế `account_type`/`is_active` hiện có (tránh breaking change), chỉ
 * là nguồn sự thật MỚI cho vòng đời đăng nhập kể từ nay.
 */
enum AccountLifecycleStatus: string
{
    case Invited   = 'invited';
    case Pending   = 'pending';
    case Active    = 'active';
    case Suspended = 'suspended';
    case Archived  = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Invited   => 'Đã mời — chưa kích hoạt',
            self::Pending   => 'Chờ xác nhận',
            self::Active    => 'Đang hoạt động',
            self::Suspended => 'Tạm khóa',
            self::Archived  => 'Đã lưu trữ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Invited   => 'badge-info',
            self::Pending   => 'badge-warning',
            self::Active    => 'badge-success',
            self::Suspended => 'badge-error',
            self::Archived  => 'badge-neutral',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Invited   => in_array($target, [self::Pending, self::Active, self::Archived], true),
            self::Pending   => in_array($target, [self::Active, self::Archived], true),
            self::Active    => in_array($target, [self::Suspended, self::Archived], true),
            self::Suspended => in_array($target, [self::Active, self::Archived], true),
            self::Archived  => false,
        };
    }
}
