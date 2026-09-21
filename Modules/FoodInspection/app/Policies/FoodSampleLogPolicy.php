<?php

namespace Modules\FoodInspection\Policies;

use App\Models\User;
use Modules\FoodInspection\Models\FoodSampleLog;

class FoodSampleLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('food_inspection.view');
    }

    public function view(User $user, FoodSampleLog $log): bool
    {
        return $user->can('food_inspection.view');
    }

    public function create(User $user): bool
    {
        return $user->can('food_inspection.manage');
    }

    public function update(User $user, FoodSampleLog $log): bool
    {
        return $user->can('food_inspection.manage');
    }

    public function delete(User $user, FoodSampleLog $log): bool
    {
        return $user->can('food_inspection.manage');
    }
}
