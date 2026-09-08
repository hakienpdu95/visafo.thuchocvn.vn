<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\RetailItemTag;
use Modules\Warehouse\Models\TraceabilityScanLog;

class TraceabilityPortalController extends Controller
{
    public function showByUid(Request $request, string $uid)
    {
        $tag = RetailItemTag::withoutTenant()
            ->where('uid', $uid)
            ->with([
                'product'             => fn ($q) => $q->withoutTenant()->with(['brand' => fn ($q) => $q->withoutTenant()]),
                'product.compliances' => fn ($q) => $q->where('status', 'active')->with('documentType'),
                'batch'               => fn ($q) => $q->withoutTenant()->with(['vendor' => fn ($q) => $q->withoutTenant()]),
            ])
            ->first();

        TraceabilityScanLog::create([
            'retail_item_tag_id' => $tag?->id,
            'scanned_code'       => $request->fullUrl(),
            'ip_address'         => $request->ip(),
            'user_agent'         => (string) $request->userAgent(),
            'created_at'         => now(),
        ]);

        if (! $tag) {
            return view('warehouse::portal.not_found');
        }

        if ($tag->status === RetailItemTagStatus::Provisioned) {
            return view('warehouse::portal.not_activated');
        }

        if (! $tag->status->isMarketReleased()) {
            return view('warehouse::portal.not_released');
        }

        $scanCount = TraceabilityScanLog::where('retail_item_tag_id', $tag->id)->count();

        return view('warehouse::portal.show', compact('tag', 'scanCount'));
    }

    public function show(Request $request, string $sku, string $batchCode, string $serial)
    {
        $tag = RetailItemTag::withoutTenant()
            ->whereHas('product', fn ($q) => $q->withoutTenant()->where('sku', $sku))
            ->whereHas('batch', fn ($q) => $q->withoutTenant()->where('internal_batch_code', $batchCode))
            ->where('serial_number', (int) $serial)
            ->with([
                'product'             => fn ($q) => $q->withoutTenant()->with(['brand' => fn ($q) => $q->withoutTenant()]),
                'product.compliances' => fn ($q) => $q->where('status', 'active')->with('documentType'),
                'batch'               => fn ($q) => $q->withoutTenant()->with(['vendor' => fn ($q) => $q->withoutTenant()]),
            ])
            ->first();

        $scannedCode = $request->fullUrl();

        TraceabilityScanLog::create([
            'retail_item_tag_id' => $tag?->id,
            'scanned_code'       => $scannedCode,
            'ip_address'         => $request->ip(),
            'user_agent'         => (string) $request->userAgent(),
            'created_at'         => now(),
        ]);

        if (! $tag) {
            return view('warehouse::portal.not_found');
        }

        if (! $tag->status->isMarketReleased()) {
            return view('warehouse::portal.not_released');
        }

        $scanCount = TraceabilityScanLog::where('retail_item_tag_id', $tag->id)->count();

        return view('warehouse::portal.show', compact('tag', 'scanCount'));
    }
}
