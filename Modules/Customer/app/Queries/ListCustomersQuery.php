<?php

namespace Modules\Customer\Queries;

use App\Shared\Contracts\QueryInterface;

class ListCustomersQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'created_at',
        public readonly string  $sortDir   = 'desc',
        public readonly ?string $search    = null,
        public readonly ?string $customerGroup = null,
        public readonly ?string $mealModel     = null,
        public readonly ?string $status        = null,
    ) {}
}
