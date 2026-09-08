<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\DocumentMasterType;

class DocumentMasterTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product.view');
    }

    public function view(User $user, DocumentMasterType $documentMasterType): bool
    {
        return $user->can('product.view');
    }

    public function create(User $user): bool
    {
        return $user->can('product.manage');
    }

    public function update(User $user, DocumentMasterType $documentMasterType): bool
    {
        return $user->can('product.manage');
    }

    public function delete(User $user, DocumentMasterType $documentMasterType): bool
    {
        return $user->can('product.manage');
    }
}
