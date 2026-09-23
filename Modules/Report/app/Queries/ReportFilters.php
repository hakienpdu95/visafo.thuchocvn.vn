<?php

namespace Modules\Report\Queries;

use App\Shared\Contracts\QueryInterface;

class ReportFilters implements QueryInterface
{
    public function __construct(
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $customer = null,
        public readonly ?string $unit = null,
    ) {}
}
