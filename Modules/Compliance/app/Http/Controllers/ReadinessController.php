<?php

namespace Modules\Compliance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Services\ReadinessScoringService;

class ReadinessController extends Controller
{
    public function index(ReadinessScoringService $scoringService): View
    {
        $this->authorize('viewAny', ComplianceDocument::class);

        return view('compliance::readiness.index', [
            'score' => $scoringService->score(),
        ]);
    }
}
