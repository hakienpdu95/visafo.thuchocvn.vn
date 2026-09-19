<?php

namespace Modules\LabelTemplate\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\LabelTemplate\Models\LabelTemplate;

class ListLabelTemplatesHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'view_path', 'default_size', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListLabelTemplatesQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'name';
        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        $q = LabelTemplate::query();

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('view_path', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($query->size !== null && $query->size !== '') {
            $q->where('default_size', $query->size);
        }

        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('created_at', '<=', $query->dateTo);
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $q->orderBy('id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
