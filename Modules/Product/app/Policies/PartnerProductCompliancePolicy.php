<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\PartnerProductCompliance;

class PartnerProductCompliancePolicy
{
    public function create(User $user): bool
    {
        return $user->can('product.manage');
    }

    public function delete(User $user, PartnerProductCompliance $compliance): bool
    {
        return $user->can('product.manage');
    }
}
