<?php

namespace Modules\HanoiCheckOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HanoiCheckOrder\Actions\Backend\LinkHanoiCheckOrderAction;
use Modules\HanoiCheckOrder\Data\Requests\LinkHanoiCheckOrderData;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\HanoiCheckOrder\Queries\ListHanoiCheckOrdersQuery;
use Modules\SalesOrder\Models\SalesOrder;

class HanoiCheckOrderController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', HanoiCheckOrderLink::class);

        $linked = HanoiCheckOrderLink::query()->whereHas('salesOrder', fn ($q) => $q->whereNull('deleted_at'))->count();

        return view('hanoicheckorder::index', [
            'statuses' => collect(SalesOrder::statusLabels())->map(fn ($text, $value) => ['value' => $value, 'text' => $text])->values()->all(),
            'links'    => [
                ['value' => ListHanoiCheckOrdersQuery::LINK_UNLINKED, 'text' => 'Chưa liên kết'],
                ['value' => ListHanoiCheckOrdersQuery::LINK_LINKED, 'text' => 'Đã liên kết'],
            ],
            'stats' => ['linked' => $linked, 'total' => SalesOrder::query()->count()],
        ]);
    }

    public function link(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('link', [HanoiCheckOrderLink::class, $salesOrder]);

        $link = LinkHanoiCheckOrderAction::run($salesOrder, LinkHanoiCheckOrderData::validateAndCreate($request->all()), $request->user()?->id);

        return response()->json([
            'message' => $link
                ? "Đã liên kết đơn {$salesOrder->misa_ref_id} với mã HanoiCheck {$link->hanoicheck_order_code}."
                : "Đã gỡ liên kết mã HanoiCheck của đơn {$salesOrder->misa_ref_id}.",
            'link' => $link ? [
                'hanoicheck_order_code' => $link->hanoicheck_order_code,
                'note'                  => $link->note,
                'linked_at'             => $link->linked_at?->format('H:i d/m/Y'),
                'linked_by'             => $link->linkedBy?->name,
            ] : null,
        ]);
    }
}
