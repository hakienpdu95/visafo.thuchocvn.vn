<?php

namespace Modules\LabelTemplate\Queries;

use App\Shared\Contracts\QueryInterface;

class ListLabelTemplatesQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly string $sortField = 'name',
        public readonly string $sortDir = 'asc',
        public readonly ?string $search = null,
        public readonly ?string $size = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {}
}
