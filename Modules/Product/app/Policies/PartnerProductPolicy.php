<?php

namespace Modules\Product\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\PartnerProduct;

class PartnerProductPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'product');
    }

    public function view(User $user, PartnerProduct $partnerProduct): bool
    {
        return ModuleAccess::view($user, 'product', $partnerProduct);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'product');
    }

    public function update(User $user, PartnerProduct $partnerProduct): bool
    {
        return ModuleAccess::update($user, 'product', $partnerProduct);
    }

    public function delete(User $user, PartnerProduct $partnerProduct): bool
    {
        return ModuleAccess::delete($user, 'product', $partnerProduct);
    }
}
