<?php

namespace Modules\Customer\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Customer\Models\Customer;

class ListCustomersHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'customer_code', 'customer_group', 'meal_model', 'status', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListCustomersQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = Customer::query()->with('pic');

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('customer_code', 'like', $term)
                    ->orWhere('tax_code', 'like', $term);
            });
        }

        if ($query->customerGroup !== null && $query->customerGroup !== '') {
            $q->where('customer_group', $query->customerGroup);
        }

        if ($query->mealModel !== null && $query->mealModel !== '') {
            $q->where('meal_model', $query->mealModel);
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('status', $query->status);
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $q->orderBy('id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
