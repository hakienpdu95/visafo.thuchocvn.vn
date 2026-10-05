<?php

namespace Modules\SalesOrder\Enums;

enum TraceReviewStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(TraceReviewType $type = TraceReviewType::Rating): string
    {
        // Sự cố không bao giờ công khai: "duyệt" = đã tiếp nhận xử lý
        return match ($this) {
            self::Pending  => $type === TraceReviewType::Issue ? 'Mới' : 'Chờ duyệt',
            self::Approved => $type === TraceReviewType::Issue ? 'Đã xử lý' : 'Đã duyệt',
            self::Rejected => $type === TraceReviewType::Issue ? 'Bỏ qua' : 'Đã ẩn',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending  => 'badge-warning',
            self::Approved => 'badge-success',
            self::Rejected => 'badge-ghost',
        };
    }
}
