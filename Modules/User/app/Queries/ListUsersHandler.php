<?php

namespace Modules\User\Queries;

use App\Models\User;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListUsersHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'name', 'email', 'department', 'is_active', 'created_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListUsersQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = User::query()->select('users.*');

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('users.name',  'like', $term)
                    ->orWhere('users.email', 'like', $term);
            });
        }

        if ($query->role !== null && $query->role !== '') {
            $role = $query->role;
            $q->whereExists(function ($sub) use ($role): void {
                $sub->selectRaw('1')
                    ->from('model_has_roles as mhr_filter')
                    ->join('roles as r_filter', 'r_filter.id', '=', 'mhr_filter.role_id')
                    ->whereColumn('mhr_filter.model_id', 'users.id')
                    ->where('mhr_filter.model_type', User::class)
                    ->where('r_filter.name', $role);
            });
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('users.is_active', $query->status === '1');
        }

        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->where('users.created_at', '>=', $query->dateFrom . ' 00:00:00');
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->where('users.created_at', '<=', $query->dateTo . ' 23:59:59');
        }

        $q->orderBy('users.' . $sortField, $sortDir);

        if ($sortField !== 'id') {
            $q->orderBy('users.id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
