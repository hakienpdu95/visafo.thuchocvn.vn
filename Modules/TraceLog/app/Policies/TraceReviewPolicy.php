<?php

namespace Modules\TraceLog\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\SalesOrder\Models\TraceReview;

/** Đánh giá & phản hồi từ trang truy xuất — dùng chung quyền module "Nhật ký TXNG" (trace_log). */
class TraceReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'trace_log');
    }

    /** Duyệt (công khai) / từ chối (ẩn). */
    public function moderate(User $user, TraceReview $review): bool
    {
        return ModuleAccess::update($user, 'trace_log');
    }
}
