<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Warehouse\Models\Batch;

class ListBatchesHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['internal_batch_code', 'mfg_batch_number', 'exp_date', 'status', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListBatchesQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'exp_date';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = Batch::query()
            ->with(['product.latestCompliance', 'vendor', 'inboundReceipt'])
            ->withCount([
                'tags',
                'tags as tags_exported_count' => fn (Builder $tagQuery) => $tagQuery->whereNotNull('external_order_id'),
                'adverseEventReports',
            ]);

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('internal_batch_code', 'like', $term)
                    ->orWhere('mfg_batch_number', 'like', $term);
            });
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
