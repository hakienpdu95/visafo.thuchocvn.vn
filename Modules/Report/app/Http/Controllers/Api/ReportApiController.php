<?php

namespace Modules\Report\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Report\Http\Controllers\Concerns\ResolvesReportFilters;
use Modules\Report\Queries\PickingReportHandler;
use Modules\Report\Queries\VolumeReportHandler;

class ReportApiController extends Controller
{
    use ResolvesReportFilters;

    public function volume(Request $request, VolumeReportHandler $handler): JsonResponse
    {
        return response()->json($handler->handle($this->filters($request, 'volume')));
    }

    public function picking(Request $request, PickingReportHandler $handler): JsonResponse
    {
        return response()->json($handler->handle($this->filters($request, 'picking')));
    }
}
