<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Report\Http\Controllers\Concerns\ResolvesReportFilters;

class ReportController extends Controller
{
    use ResolvesReportFilters;

    public function index(): RedirectResponse
    {
        return redirect()->route('backend.reports.volume');
    }

    public function volume()
    {
        return view('report::volume', $this->pageData('volume'));
    }

    public function picking()
    {
        return view('report::picking', $this->pageData('picking'));
    }

    private function pageData(string $report): array
    {
        $filters = $this->filters(request(), $report);

        $customers = DB::table('sales_orders')->whereNull('deleted_at')->whereNotNull('customer_name')->where('customer_name', '!=', '')
            ->distinct()->orderBy('customer_name')->pluck('customer_name')
            ->map(fn ($name) => ['value' => $name, 'text' => $name])->all();

        $units = DB::table('sales_order_items')->whereNull('deleted_at')->whereNotNull('unit_raw')->where('unit_raw', '!=', '')
            ->selectRaw('MIN(unit_raw) as unit')->groupByRaw('LOWER(TRIM(unit_raw))')->orderBy('unit')->pluck('unit')
            ->map(fn ($unit) => ['value' => $unit, 'text' => $unit])->all();

        return [
            'customers' => $customers,
            'units'     => $units,
            'defaults'  => [
                'date_from' => $filters->dateFrom,
                'date_to'   => $filters->dateTo,
                'date'      => $filters->dateFrom,
            ],
        ];
    }
}
