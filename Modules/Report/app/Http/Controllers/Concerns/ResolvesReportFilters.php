<?php

namespace Modules\Report\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Modules\Report\Queries\ReportFilters;

trait ResolvesReportFilters
{
    protected function filters(Request $request, string $report): ReportFilters
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
            'date'      => ['nullable', 'date'],
            'customer'  => ['nullable', 'string', 'max:255'],
            'unit'      => ['nullable', 'string', 'max:30'],
        ]);

        if ($report === 'picking') {
            $date = $data['date'] ?? now()->addDay()->toDateString();

            return new ReportFilters(dateFrom: $date, dateTo: $date, customer: $data['customer'] ?? null);
        }

        return new ReportFilters(
            dateFrom: $data['date_from'] ?? now()->startOfMonth()->toDateString(),
            dateTo: $data['date_to'] ?? now()->addDay()->toDateString(),
            customer: $data['customer'] ?? null,
            unit: $data['unit'] ?? null,
        );
    }
}
