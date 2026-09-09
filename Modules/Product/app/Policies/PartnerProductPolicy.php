<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\PartnerProduct;

class PartnerProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product.view');
    }

    public function view(User $user, PartnerProduct $partnerProduct): bool
    {
        return $user->can('product.view');
    }

    public function create(User $user): bool
    {
        return $user->can('product.manage');
    }

    public function update(User $user, PartnerProduct $partnerProduct): bool
    {
        return $user->can('product.manage');
    }

    public function delete(User $user, PartnerProduct $partnerProduct): bool
    {
        return $user->can('product.manage');
    }
}
