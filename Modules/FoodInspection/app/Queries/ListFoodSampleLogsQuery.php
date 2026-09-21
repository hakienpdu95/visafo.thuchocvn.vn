<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryInterface;

class ListFoodSampleLogsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly string $sortField = 'sample_date',
        public readonly string $sortDir = 'desc',
        public readonly ?string $search = null,
        public readonly ?string $customerId = null,
        public readonly ?string $status = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {}
}
