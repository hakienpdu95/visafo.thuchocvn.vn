<?php

namespace Modules\SalesPackage\Queries;

use App\Shared\Contracts\QueryInterface;

class ListSalesPackagesQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?string $search = null,
        public readonly ?string $status = null,
    ) {}
}
