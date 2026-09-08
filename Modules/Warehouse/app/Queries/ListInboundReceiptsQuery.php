<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryInterface;

class ListInboundReceiptsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'received_date',
        public readonly string  $sortDir   = 'desc',
        public readonly ?string $search    = null,
        public readonly ?string $status    = null,
    ) {}
}
