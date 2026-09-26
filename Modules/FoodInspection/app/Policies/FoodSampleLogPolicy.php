<?php

namespace Modules\FoodInspection\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\FoodInspection\Models\FoodSampleLog;

class FoodSampleLogPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'food_inspection');
    }

    public function view(User $user, FoodSampleLog $log): bool
    {
        return ModuleAccess::view($user, 'food_inspection', $log);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'food_inspection');
    }

    public function update(User $user, FoodSampleLog $log): bool
    {
        return ModuleAccess::update($user, 'food_inspection', $log);
    }

    public function delete(User $user, FoodSampleLog $log): bool
    {
        return ModuleAccess::delete($user, 'food_inspection', $log);
    }
}
