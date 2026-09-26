<?php

namespace Modules\SalesPackage\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\SalesPackage\Models\SalesPackage;

class ListSalesPackagesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListSalesPackagesQuery $query */
        $q = SalesPackage::query()->visibleTo()->with('customer')->withCount('items');

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term));
            });
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('status', $query->status);
        }

        $q->orderByDesc('created_at');

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
