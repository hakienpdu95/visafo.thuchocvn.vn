<?php

namespace Modules\TraceLog\Policies;

use App\Models\User;
use Modules\SalesOrder\Models\PrintLog;

class TraceLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('trace_log.view');
    }

    public function view(User $user, PrintLog $printLog): bool
    {
        return $user->can('trace_log.view');
    }

    /** QC đổi trạng thái tem (thu hồi / lỗi / phục hồi). */
    public function changeStatus(User $user, PrintLog $printLog): bool
    {
        return $user->can('trace_log.manage');
    }
}
