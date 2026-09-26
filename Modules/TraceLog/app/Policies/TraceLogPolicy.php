<?php

namespace Modules\TraceLog\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\SalesOrder\Models\PrintLog;

class TraceLogPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'trace_log');
    }

    public function view(User $user, PrintLog $printLog): bool
    {
        return ModuleAccess::view($user, 'trace_log', $printLog);
    }

    /** QC đổi trạng thái tem (thu hồi / lỗi / phục hồi). */
    public function changeStatus(User $user, PrintLog $printLog): bool
    {
        return ModuleAccess::update($user, 'trace_log', $printLog);
    }
}
