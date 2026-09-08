<?php

namespace Modules\Recall\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Recall\Models\ProductRecall;

class ListProductRecallsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListProductRecallsQuery $query */
        $q = ProductRecall::query()->with(['product', 'batch'])->latest('initiated_at');

        if ($query->status !== null && $query->status !== '') {
            $q->where('status', $query->status);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
