<?php

namespace Modules\Employee\Queries;

use App\Shared\Contracts\QueryInterface;

class ListEmployeesQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page          = 1,
        public readonly int     $perPage       = 25,
        public readonly string  $sortField     = 'full_name',
        public readonly string  $sortDir       = 'asc',
        public readonly ?string $search        = null,
        public readonly ?string $departmentId  = null,
    ) {}
}
