<?php

namespace Modules\Employee\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Employee\Models\Employee;

class ListEmployeesHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['full_name', 'email', 'job_title', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'full_name';
        $sortDir   = $query->sortDir === 'desc' ? 'desc' : 'asc';

        $q = Employee::query()
            ->with(['departments', 'latestHealthCheck', 'latestAttpTraining']);

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('full_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('job_title', 'like', $term);
            });
        }

        if ($query->departmentId !== null && $query->departmentId !== '') {
            $q->whereHas('departments', fn (Builder $sub) => $sub->where('departments.id', $query->departmentId));
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $q->orderBy('id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
