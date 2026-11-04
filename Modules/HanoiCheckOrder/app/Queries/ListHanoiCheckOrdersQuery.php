<?php

namespace Modules\HanoiCheckOrder\Queries;

use App\Shared\Contracts\QueryInterface;

class ListHanoiCheckOrdersQuery implements QueryInterface
{
    public const LINK_LINKED = 'linked';

    public const LINK_UNLINKED = 'unlinked';

    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly string $sortField = 'delivery_date',
        public readonly string $sortDir = 'desc',
        public readonly ?string $search = null,
        public readonly ?string $link = null,
        public readonly ?string $status = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {}
}
