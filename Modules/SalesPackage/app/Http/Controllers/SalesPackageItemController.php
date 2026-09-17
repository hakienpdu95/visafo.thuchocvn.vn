<?php

namespace Modules\SalesPackage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\SalesPackage\Actions\Backend\DestroySalesPackageItemAction;
use Modules\SalesPackage\Actions\Backend\UpdateSalesPackageItemAction;
use Modules\SalesPackage\Data\Requests\UpdateSalesPackageItemData;
use Modules\SalesPackage\Models\SalesPackage;
use Modules\SalesPackage\Models\SalesPackageItem;
use Modules\SalesPackage\Services\CustomerComplianceRuleEngine;

class SalesPackageItemController extends Controller
{
    public function update(
        Request $request,
        SalesPackage $salesPackage,
        SalesPackageItem $item,
        UpdateSalesPackageItemAction $action,
        CustomerComplianceRuleEngine $ruleEngine,
    ): RedirectResponse {
        $this->authorize('update', $salesPackage);
        abort_unless($item->sales_package_id === $salesPackage->id, 404);
        abort_unless($item->is_custom, 422, 'Chỉ có thể sửa tài liệu bổ sung ngoài hệ thống.');

        $data = UpdateSalesPackageItemData::validateAndCreate($request->all());

        DB::transaction(function () use ($salesPackage, $item, $data, $action, $ruleEngine) {
            $action->handle($item, $data);
            $this->recomputeScore($salesPackage, $ruleEngine);
        });

        return redirect()->route('backend.sales-packages.show', $salesPackage)
            ->with('success', 'Đã cập nhật tài liệu bổ sung.');
    }

    public function destroy(
        SalesPackage $salesPackage,
        SalesPackageItem $item,
        DestroySalesPackageItemAction $action,
        CustomerComplianceRuleEngine $ruleEngine,
    ): RedirectResponse {
        $this->authorize('update', $salesPackage);
        abort_unless($item->sales_package_id === $salesPackage->id, 404);
        abort_unless($item->is_custom, 422, 'Chỉ có thể xóa tài liệu bổ sung ngoài hệ thống.');

        $name = null;
        DB::transaction(function () use ($salesPackage, $item, $action, $ruleEngine, &$name) {
            $name = $action->handle($item);
            $this->recomputeScore($salesPackage, $ruleEngine);
        });

        return redirect()->route('backend.sales-packages.show', $salesPackage)
            ->with('success', 'Đã xóa tài liệu "' . $name . '".');
    }

    private function recomputeScore(SalesPackage $salesPackage, CustomerComplianceRuleEngine $ruleEngine): void
    {
        $results   = $ruleEngine->evaluate($salesPackage->customer);
        $total     = count($results);
        $satisfied = count(array_filter($results, fn ($r) => $r->satisfied));
        $score     = $total > 0 ? (int) round($satisfied / $total * 100) : 0;

        $salesPackage->update(['readiness_score' => $score]);
    }
}
