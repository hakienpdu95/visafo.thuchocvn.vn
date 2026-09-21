<?php

namespace Modules\FoodInspection\Policies;

use App\Models\User;
use Modules\FoodInspection\Models\FoodInspectionStep2Log;

class FoodInspectionStep2LogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('food_inspection.view');
    }

    public function view(User $user, FoodInspectionStep2Log $log): bool
    {
        return $user->can('food_inspection.view');
    }

    public function create(User $user): bool
    {
        return $user->can('food_inspection.manage');
    }

    public function update(User $user, FoodInspectionStep2Log $log): bool
    {
        return $user->can('food_inspection.manage');
    }

    public function delete(User $user, FoodInspectionStep2Log $log): bool
    {
        return $user->can('food_inspection.manage');
    }
}
