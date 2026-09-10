<?php

namespace Modules\Compliance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Compliance\Enums\ReadinessStatus;
use Modules\Compliance\Models\ComplianceDocument;

class ReadinessController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ComplianceDocument::class);

        $statuses = collect(ReadinessStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('compliance::readiness.index', [
            'statuses' => $statuses,
        ]);
    }
}
