<?php

namespace Modules\Recall\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Models\Product;
use Modules\Recall\Actions\CloseAdverseEventReportAction;
use Modules\Recall\Actions\LookupTagForAdverseEventAction;
use Modules\Recall\Actions\StoreAdverseEventReportAction;
use Modules\Recall\Actions\SubmitAdverseEventReportAction;
use Modules\Recall\Data\Requests\StoreAdverseEventReportData;
use Modules\Recall\Enums\AdverseEventStatus;
use Modules\Recall\Models\AdverseEventReport;
use Modules\Recall\Queries\GetAdverseEventReportHandler;
use Modules\Recall\Queries\GetAdverseEventReportQuery;
use Modules\Recall\Queries\ListAdverseEventReportsHandler;
use Modules\Recall\Queries\ListAdverseEventReportsQuery;
use Spatie\LaravelPdf\Facades\Pdf;

class AdverseEventReportController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AdverseEventReport::class, 'report');
    }

    public function index(Request $request, ListAdverseEventReportsHandler $handler)
    {
        $reports = $handler->handle(new ListAdverseEventReportsQuery(
            page:    max(1, (int) $request->integer('page', 1)),
            perPage: 25,
            status:  $request->input('status'),
        ));

        $statuses = collect(AdverseEventStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])->all();

        return view('recall::adverse_event_reports.index', compact('reports', 'statuses'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();

        return view('recall::adverse_event_reports.create', compact('products'));
    }

    public function lookupTag(Request $request, LookupTagForAdverseEventAction $action)
    {
        $this->authorize('create', AdverseEventReport::class);

        $validated = $request->validate(['code' => ['required', 'string', 'max:500']]);

        $tag = $action->handle($validated['code']);

        if (! $tag || ! $tag->product) {
            return response()->json(['found' => false, 'message' => 'Không tìm thấy tem này trong hệ thống.'], 404);
        }

        return response()->json([
            'found'               => true,
            'product_id'          => $tag->product_id,
            'product_name'        => $tag->product->name,
            'product_sku'         => $tag->product->sku,
            'batch_id'            => $tag->batch_id,
            'batch_code'          => $tag->batch?->internal_batch_code,
            'mfg_date'            => $tag->batch?->mfg_date?->format('d/m/Y'),
            'exp_date'            => $tag->batch?->exp_date?->format('d/m/Y'),
            'manufacturer_origin' => $tag->batch?->vendor?->name,
        ]);
    }

    public function store(Request $request, StoreAdverseEventReportAction $action): RedirectResponse
    {
        $input = $request->all();
        foreach (['batch_id', 'lot_number_manual', 'mfg_or_exp_date_manual', 'consumer_age', 'consumer_gender', 'onset_at', 'outcome', 'outcome_date', 'report_source'] as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }
        $input['was_hospitalized']           = $request->boolean('was_hospitalized');
        $input['required_medical_treatment'] = $request->boolean('required_medical_treatment');

        $data   = StoreAdverseEventReportData::validateAndCreate($input);
        $report = $action->handle($data);

        return redirect()->route('backend.adverse-event-reports.show', $report)
            ->with('success', 'Đã ghi nhận báo cáo tác dụng bất lợi.');
    }

    public function show(AdverseEventReport $report, GetAdverseEventReportHandler $handler)
    {
        $report = $handler->handle(new GetAdverseEventReportQuery($report));

        return view('recall::adverse_event_reports.show', compact('report'));
    }

    public function submit(AdverseEventReport $report, SubmitAdverseEventReportAction $action): RedirectResponse
    {
        $this->authorize('update', $report);

        $action->handle($report);

        return redirect()->route('backend.adverse-event-reports.show', $report)
            ->with('success', 'Đã đánh dấu báo cáo là đã nộp Cục Quản lý Dược.');
    }

    public function close(AdverseEventReport $report, CloseAdverseEventReportAction $action): RedirectResponse
    {
        $this->authorize('update', $report);

        $action->handle($report);

        return redirect()->route('backend.adverse-event-reports.show', $report)
            ->with('success', 'Đã đóng báo cáo.');
    }

    public function printPdf(AdverseEventReport $report)
    {
        $this->authorize('view', $report);

        return Pdf::view('recall::adverse_event_reports.pdf', compact('report'))
            ->format('a4')
            ->withBrowsershot(fn ($browsershot) => $browsershot->setChromePath(config('warehouse.chrome_path', '/usr/bin/google-chrome'))->noSandbox())
            ->download('phu-luc-18-mp-' . $report->id . '.pdf');
    }
}
