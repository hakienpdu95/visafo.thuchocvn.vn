<?php

namespace Modules\Contract\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Contract\Enums\ContractStatus;
use Modules\Contract\Models\Contract;

class DueForRenewalContractsHandler implements QueryHandlerInterface
{
    /**
     * @return Collection<int, Contract>
     */
    public function handle(QueryInterface $query): Collection
    {
        return Contract::query()
            ->where('is_auto_renew', true)
            ->where('status', ContractStatus::Active->value)
            ->whereNotNull('end_date')
            ->whereNotNull('renewal_period_months')
            ->whereDate('end_date', '<=', today())
            ->get();
    }
}
