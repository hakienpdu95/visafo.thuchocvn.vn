<?php

namespace Modules\User\Queries;

use App\Shared\Contracts\QueryInterface;

class ListUsersQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,

        public readonly string  $sortField = 'created_at',
        public readonly string  $sortDir   = 'desc',

        public readonly ?string $search    = null,

        public readonly ?string $role      = null,
        public readonly ?string $status    = null,

        public readonly ?string $dateFrom  = null,
        public readonly ?string $dateTo    = null,
    ) {}
}
