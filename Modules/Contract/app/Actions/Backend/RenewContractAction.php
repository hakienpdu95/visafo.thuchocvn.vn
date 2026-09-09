<?php

namespace Modules\Contract\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Contract\Models\Contract;

class RenewContractAction
{
    use AsAction;

    /**
     * Đẩy end_date thêm renewal_period_months. Contract dùng LogsActivity
     * (qua TenantAwareModel) nên update() tự ghi activity_log — không cần
     * ghi log thủ công ở đây.
     */
    public function handle(Contract $contract): Contract
    {
        $contract->update([
            'end_date' => $contract->end_date->copy()->addMonthsNoOverflow($contract->renewal_period_months),
        ]);

        return $contract;
    }
}
