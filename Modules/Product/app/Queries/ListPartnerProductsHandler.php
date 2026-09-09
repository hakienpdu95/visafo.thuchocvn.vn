<?php

namespace Modules\Product\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Product\Models\PartnerProduct;

class ListPartnerProductsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'vendor_sku', 'status', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPartnerProductsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = PartnerProduct::query()->with(['vendor', 'product']);

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('vendor_sku', 'like', $term)
                    ->orWhereHas('vendor', fn (Builder $v) => $v->where('name', 'like', $term))
                    ->orWhereHas('product', fn (Builder $p) => $p->where('name', 'like', $term));
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
