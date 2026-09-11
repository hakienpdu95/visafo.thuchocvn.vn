<?php

namespace Modules\Product\Policies;

use App\Enums\RoleEnum;
use App\Models\User;
use Modules\Product\Models\FarmingBatch;

class FarmingBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compliance.view');
    }

    public function view(User $user, FarmingBatch $farmingBatch): bool
    {
        return $user->can('compliance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('compliance.manage');
    }

    public function update(User $user, FarmingBatch $farmingBatch): bool
    {
        return $user->can('compliance.manage');
    }

    public function delete(User $user, FarmingBatch $farmingBatch): bool
    {
        return $user->can('compliance.manage');
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
