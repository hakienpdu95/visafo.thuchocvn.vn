<?php

namespace Modules\Contract\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Contract\Models\Contract;

class ListPartyContractsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListPartyContractsQuery $query */
        if (! auth()->user()?->can('viewAny', Contract::class)) {
            return collect();
        }

        return Contract::query()
            ->visibleTo()
            ->with(['contractType', 'media'])
            ->when($query->vendorId, fn ($q, $id) => $q->where('vendor_id', $id))
            ->when($query->customerId, fn ($q, $id) => $q->where('customer_id', $id))
            ->orderByDesc('created_at')
            ->get();
    }
}
