<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\SalesOrder\Queries\GetTraceabilityHandler;
use Modules\SalesOrder\Queries\GetTraceabilityQuery;

/**
 * Trang truy xuất nguồn gốc CÔNG KHAI (không đăng nhập) — người tiêu dùng quét QR trên tem.
 */
class TraceController extends Controller
{
    public function show(string $traceCode, GetTraceabilityHandler $handler)
    {
        $trace = $handler->handle(new GetTraceabilityQuery($traceCode));

        if ($trace === null) {
            return response()->view('traceability.not-found', ['traceCode' => $traceCode], 404);
        }

        return view('traceability.show', ['trace' => $trace]);
    }
}
