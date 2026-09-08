<?php

namespace Modules\Recall\Policies;

use App\Models\User;
use Modules\Recall\Models\AdverseEventReport;

class AdverseEventReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('recall.view');
    }

    public function view(User $user, AdverseEventReport $report): bool
    {
        return $user->can('recall.view');
    }

    public function create(User $user): bool
    {
        return $user->can('recall.manage');
    }

    public function update(User $user, AdverseEventReport $report): bool
    {
        return $user->can('recall.manage');
    }
}
