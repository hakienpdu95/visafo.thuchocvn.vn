<?php

namespace Modules\TraceLog\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\SalesOrder\Models\SalesOrder;

class ListTraceLogCustomersHandler implements QueryHandlerInterface
{
    /** @return array<int, array{value: string, text: string}> */
    public function handle(QueryInterface $query): array
    {
        return SalesOrder::query()
            ->whereNotNull('customer_name')->where('customer_name', '!=', '')
            ->distinct()->orderBy('customer_name')
            ->pluck('customer_name')
            ->map(fn (string $name) => ['value' => $name, 'text' => $name])
            ->all();
    }
}
