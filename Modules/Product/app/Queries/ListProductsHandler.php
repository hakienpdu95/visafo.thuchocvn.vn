<?php

namespace Modules\Product\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Product\Models\Product;

class ListProductsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'sku', 'status', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListProductsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = Product::query()->with(['category', 'latestCompliance.documentType']);

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('barcode', 'like', $term);
            });
        }

        if ($query->categoryId !== null && $query->categoryId !== '') {
            $q->where('category_id', $query->categoryId);
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
