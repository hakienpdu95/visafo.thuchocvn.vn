<?php

namespace Modules\Contract\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Contract\Models\Contract;

class ListContractsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['contract_number', 'name', 'start_date', 'end_date', 'total_value', 'status', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListContractsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = Contract::query()->with(['vendor', 'contractType']);

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('contract_number', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhereHas('vendor', fn (Builder $v) => $v->where('name', 'like', $term));
            });
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('status', $query->status);
        }

        if ($query->vendorId !== null && $query->vendorId !== '') {
            $q->where('vendor_id', $query->vendorId);
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $q->orderBy('id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
