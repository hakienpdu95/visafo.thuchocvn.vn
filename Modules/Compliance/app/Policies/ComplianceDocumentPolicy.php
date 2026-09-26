<?php

namespace Modules\Compliance\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Compliance\Models\ComplianceDocument;

class ComplianceDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'compliance');
    }

    public function view(User $user, ComplianceDocument $document): bool
    {
        return ModuleAccess::view($user, 'compliance', $document);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'compliance');
    }

    public function update(User $user, ComplianceDocument $document): bool
    {
        return ModuleAccess::update($user, 'compliance', $document);
    }

    public function delete(User $user, ComplianceDocument $document): bool
    {
        return ModuleAccess::delete($user, 'compliance', $document);
    }
}
