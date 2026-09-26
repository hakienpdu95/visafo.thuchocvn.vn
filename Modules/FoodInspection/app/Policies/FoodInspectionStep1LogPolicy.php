<?php

namespace Modules\FoodInspection\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;

class FoodInspectionStep1LogPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'food_inspection');
    }

    public function view(User $user, FoodInspectionStep1Log $log): bool
    {
        return ModuleAccess::view($user, 'food_inspection', $log);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'food_inspection');
    }

    public function update(User $user, FoodInspectionStep1Log $log): bool
    {
        return ModuleAccess::update($user, 'food_inspection', $log);
    }

    public function delete(User $user, FoodInspectionStep1Log $log): bool
    {
        return ModuleAccess::delete($user, 'food_inspection', $log);
    }
}
