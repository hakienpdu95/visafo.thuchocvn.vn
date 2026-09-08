<?php

namespace App\Http\Controllers\Backend\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * taskThroughput/leadFunnel/workflowHealth đã bị gỡ cùng module Task/Lead/
 * WorkflowAutomation; headcount (dựa trên Employee/Department) đã bị gỡ cùng
 * các module đó (cleanup/remove-non-competency-modules).
 */
class DashboardChartController extends Controller
{
    // ── Headcount by Department — donut chart ─────────────────────────────
    public function headcount(): JsonResponse
    {
        return response()->json(['departments' => []]);
    }
}
