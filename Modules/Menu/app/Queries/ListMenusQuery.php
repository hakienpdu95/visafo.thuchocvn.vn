<?php

namespace Modules\Menu\Queries;

use App\Shared\Contracts\QueryInterface;

class ListMenusQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly string $sortField = 'menu_date',
        public readonly string $sortDir = 'desc',
        public readonly ?string $search = null,
        public readonly ?string $customerId = null,
        public readonly ?string $mealTime = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {}
}
