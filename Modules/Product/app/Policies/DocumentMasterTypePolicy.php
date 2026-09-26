<?php

namespace Modules\Product\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\DocumentMasterType;

class DocumentMasterTypePolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'product');
    }

    public function view(User $user, DocumentMasterType $documentMasterType): bool
    {
        return ModuleAccess::view($user, 'product', $documentMasterType);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'product');
    }

    public function update(User $user, DocumentMasterType $documentMasterType): bool
    {
        return ModuleAccess::update($user, 'product', $documentMasterType);
    }

    public function delete(User $user, DocumentMasterType $documentMasterType): bool
    {
        return ModuleAccess::delete($user, 'product', $documentMasterType);
    }
}
