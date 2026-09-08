<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Warehouse\Actions\Backend\BindRetailItemTagDiscreteAction;
use Modules\Warehouse\Enums\BatchStatus;
use Modules\Warehouse\Models\Batch;

class TagScanBindController extends Controller
{
    public function create()
    {
        abort_unless(auth()->user()->can('warehouse.manage'), 403);

        $batches = Batch::where('status', '!=', BatchStatus::Recalled->value)
            ->latest('created_at')
            ->limit(200)
            ->get(['id', 'internal_batch_code', 'product_id']);

        return view('warehouse::tag_scan_bind.create', compact('batches'));
    }

    public function scan(Request $request, BindRetailItemTagDiscreteAction $action): JsonResponse
    {
        abort_unless(auth()->user()->can('warehouse.manage'), 403);

        $validated = $request->validate([
            'batch_id' => ['required', 'string', 'exists:batches,id'],
            'scanned'  => ['required', 'string'],
        ]);

        $batch = Batch::findOrFail($validated['batch_id']);

        try {
            $tag = $action->handle($batch, $validated['scanned']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'success'      => true,
            'message'      => "Đã gắn kết — serial #{$tag->serial_number}",
            'serial'       => $tag->serial_number,
            'gs1_serial'   => $tag->gs1_serial,
        ]);
    }
}
