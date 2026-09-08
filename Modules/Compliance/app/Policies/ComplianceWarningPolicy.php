<?php

namespace Modules\Compliance\Policies;

use App\Models\User;
use Modules\Compliance\Models\ComplianceWarning;

class ComplianceWarningPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compliance.view');
    }

    public function view(User $user, ComplianceWarning $warning): bool
    {
        return $user->can('compliance.view');
    }

    public function update(User $user, ComplianceWarning $warning): bool
    {
        return $user->can('compliance.manage');
    }
}
