<?php

namespace Modules\Compliance\Policies;

use App\Models\User;
use Modules\Compliance\Models\ComplianceDocument;

class ComplianceDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compliance.view');
    }

    public function view(User $user, ComplianceDocument $document): bool
    {
        return $user->can('compliance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('compliance.manage');
    }

    public function update(User $user, ComplianceDocument $document): bool
    {
        return $user->can('compliance.manage');
    }

    public function delete(User $user, ComplianceDocument $document): bool
    {
        return $user->can('compliance.manage');
    }
}
