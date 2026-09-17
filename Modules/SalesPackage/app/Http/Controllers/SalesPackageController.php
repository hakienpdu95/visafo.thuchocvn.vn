<?php

namespace Modules\SalesPackage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Customer\Models\Customer;
use Modules\SalesPackage\Actions\Backend\DestroySalesPackageAction;
use Modules\SalesPackage\Actions\Backend\StoreSalesPackageAction;
use Modules\SalesPackage\Actions\Backend\UpdateSalesPackageAction;
use Modules\SalesPackage\Actions\Backend\UpdateSalesPackageStatusAction;
use Modules\SalesPackage\Data\Requests\StoreSalesPackageData;
use Modules\SalesPackage\Data\Requests\UpdateSalesPackageData;
use Modules\SalesPackage\Enums\SalesPackageStatus;
use Modules\SalesPackage\Models\SalesPackage;
use Modules\SalesPackage\Queries\GetSalesPackageHandler;
use Modules\SalesPackage\Queries\GetSalesPackageQuery;
use Modules\SalesPackage\Services\CustomerComplianceRuleEngine;
use Modules\SalesPackage\Services\SalesPackageExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesPackageController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SalesPackage::class, 'sales_package');
    }

    public function index()
    {
        $statuses = collect(SalesPackageStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('salespackage::index', compact('statuses'));
    }

    public function create()
    {
        $customers = Customer::query()->orderBy('name')->get(['id', 'name', 'customer_code', 'meal_model']);

        return view('salespackage::create', compact('customers'));
    }

    public function store(Request $request, StoreSalesPackageAction $action, CustomerComplianceRuleEngine $ruleEngine): RedirectResponse
    {
        $data    = StoreSalesPackageData::validateAndCreate($request->all());
        $package = $action->handle($data, $ruleEngine);

        return redirect()->route('backend.sales-packages.show', $package)
            ->with('success', 'Đã tạo gói chào hàng "' . $package->name . '".');
    }

    public function show(SalesPackage $salesPackage, GetSalesPackageHandler $handler)
    {
        $package = $handler->handle(new GetSalesPackageQuery($salesPackage));

        return view('salespackage::show', compact('package'));
    }

    public function edit(SalesPackage $salesPackage, GetSalesPackageHandler $handler)
    {
        $package = $handler->handle(new GetSalesPackageQuery($salesPackage));

        $existingDocumentIds = $package->items()
            ->where('is_custom', false)
            ->pluck('compliance_document_id')
            ->all();

        $existingCustomDocuments = $package->items()
            ->where('is_custom', true)
            ->get()
            ->map(fn ($item) => ['name' => $item->custom_name, 'fileUrl' => $item->fileUrl()])
            ->all();

        return view('salespackage::edit', [
            'package'                 => $package,
            'existingDocumentIds'    => $existingDocumentIds,
            'existingCustomDocuments' => $existingCustomDocuments,
        ]);
    }

    public function update(Request $request, SalesPackage $salesPackage, UpdateSalesPackageAction $action, CustomerComplianceRuleEngine $ruleEngine): RedirectResponse
    {
        $data = UpdateSalesPackageData::validateAndCreate($request->all());

        if ($data->status !== null) {
            $target = SalesPackageStatus::from($data->status);
            $current = $salesPackage->status;

            if ($target !== $current && ! in_array($target, $current->transitions(), true)) {
                return redirect()->route('backend.sales-packages.edit', $salesPackage)
                    ->withErrors(['status' => 'Không thể chuyển sang trạng thái "' . $target->label() . '" từ trạng thái hiện tại.'])
                    ->withInput();
            }
        }

        $package = $action->handle($salesPackage, $data, $ruleEngine);

        return redirect()->route('backend.sales-packages.show', $package)
            ->with('success', 'Đã cập nhật gói chào hàng "' . $package->name . '".');
    }

    public function updateStatus(Request $request, SalesPackage $salesPackage, UpdateSalesPackageStatusAction $action): RedirectResponse
    {
        $this->authorize('updateStatus', $salesPackage);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(SalesPackageStatus::class)],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.enum'     => 'Trạng thái không hợp lệ.',
        ]);

        $target = SalesPackageStatus::from($validated['status']);

        if (! in_array($target, $salesPackage->status->transitions(), true)) {
            return redirect()->route('backend.sales-packages.show', $salesPackage)
                ->with('error', 'Không thể chuyển gói sang trạng thái "' . $target->label() . '" từ trạng thái hiện tại.');
        }

        $action->handle($salesPackage, $target);

        return redirect()->route('backend.sales-packages.show', $salesPackage)
            ->with('success', 'Đã chuyển gói sang trạng thái "' . $target->label() . '".');
    }

    public function destroy(Request $request, SalesPackage $salesPackage, DestroySalesPackageAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($salesPackage);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa gói chào hàng "' . $name . '".']);
        }

        return redirect()->route('backend.sales-packages.index')
            ->with('success', 'Đã xóa gói chào hàng "' . $name . '".');
    }

    public function export(SalesPackage $salesPackage, SalesPackageExportService $exportService): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('view', $salesPackage);

        if ($salesPackage->items()->doesntExist()) {
            return redirect()->route('backend.sales-packages.show', $salesPackage)
                ->with('error', 'Gói chưa có tài liệu nào — không thể xuất.');
        }

        $zipPath = $exportService->export($salesPackage);

        return response()->download($zipPath, $exportService->downloadName($salesPackage))
            ->deleteFileAfterSend(true);
    }
}
