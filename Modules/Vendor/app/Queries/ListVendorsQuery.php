<?php

namespace Modules\Vendor\Queries;

use App\Shared\Contracts\QueryInterface;

class ListVendorsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'created_at',
        public readonly string  $sortDir   = 'desc',
        public readonly ?string $search    = null,
        public readonly ?string $status    = null,
    ) {}
}
