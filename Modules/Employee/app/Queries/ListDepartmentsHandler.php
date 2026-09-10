<?php

namespace Modules\Employee\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Employee\Models\Department;

class ListDepartmentsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'is_food_contact', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListDepartmentsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'name';
        $sortDir   = $query->sortDir === 'desc' ? 'desc' : 'asc';

        $q = Department::query()->withCount('employees');

        if ($query->search !== null && $query->search !== '') {
            $q->where('name', 'like', '%' . $query->search . '%');
        }

        if ($query->foodContact !== null && $query->foodContact !== '') {
            $q->where('is_food_contact', $query->foodContact === 'yes');
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $q->orderBy('id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
