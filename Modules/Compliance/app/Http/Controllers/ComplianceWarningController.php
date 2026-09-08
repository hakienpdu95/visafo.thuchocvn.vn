<?php

namespace Modules\Compliance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Compliance\Actions\AcknowledgeWarningAction;
use Modules\Compliance\Actions\ResolveWarningAction;
use Modules\Compliance\Enums\WarningCategory;
use Modules\Compliance\Enums\WarningSeverity;
use Modules\Compliance\Enums\WarningStatus;
use Modules\Compliance\Models\ComplianceWarning;

class ComplianceWarningController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ComplianceWarning::class, 'warning');
    }

    public function index()
    {
        $statuses   = collect(WarningStatus::cases())->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])->all();
        $categories = collect(WarningCategory::cases())->map(fn ($c) => ['value' => $c->value, 'text' => $c->label()])->all();
        $severities = collect(WarningSeverity::cases())->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])->all();

        return view('compliance::warnings.index', compact('statuses', 'categories', 'severities'));
    }

    public function acknowledge(ComplianceWarning $warning, AcknowledgeWarningAction $action): RedirectResponse
    {
        $this->authorize('update', $warning);

        $action->handle($warning);

        return redirect()->route('backend.compliance-warnings.index')
            ->with('success', 'Đã ghi nhận cảnh báo.');
    }

    public function resolve(ComplianceWarning $warning, ResolveWarningAction $action): RedirectResponse
    {
        $this->authorize('update', $warning);

        $action->handle($warning);

        return redirect()->route('backend.compliance-warnings.index')
            ->with('success', 'Đã đánh dấu cảnh báo là xử lý xong.');
    }
}
