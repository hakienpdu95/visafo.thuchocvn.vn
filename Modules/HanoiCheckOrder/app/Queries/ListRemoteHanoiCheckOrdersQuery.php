<?php

namespace Modules\HanoiCheckOrder\Queries;

use App\Shared\Contracts\QueryInterface;

class ListRemoteHanoiCheckOrdersQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $keyword = null,
        public readonly int $perPage = 50,
    ) {}
}
