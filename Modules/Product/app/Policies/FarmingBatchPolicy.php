<?php

namespace Modules\Product\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\Product\Models\FarmingBatch;

class FarmingBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'compliance');
    }

    public function view(User $user, FarmingBatch $farmingBatch): bool
    {
        return ModuleAccess::view($user, 'compliance', $farmingBatch);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'compliance');
    }

    public function update(User $user, FarmingBatch $farmingBatch): bool
    {
        return ModuleAccess::update($user, 'compliance', $farmingBatch);
    }

    public function delete(User $user, FarmingBatch $farmingBatch): bool
    {
        return ModuleAccess::delete($user, 'compliance', $farmingBatch);
    }

    /**
     * Nút "Phê duyệt cho phép thu hoạch" — quyền sinh tử, chỉ role qa_qc_manager
     * (không dùng permission chung compliance.manage như các thao tác khác).
     */
    public function approveHarvest(User $user, FarmingBatch $farmingBatch): bool
    {
        return $user->hasRole(RoleEnum::QA_QC_MANAGER->value);
    }
}
