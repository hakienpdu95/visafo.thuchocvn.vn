<?php

namespace Modules\HanoiCheckOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Models\HanoiCheckSyncLog;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheckOrder\Actions\Backend\LinkHanoiCheckOrderAction;
use Modules\HanoiCheckOrder\Actions\Backend\QueueHanoiCheckOrderSyncAction;
use Modules\HanoiCheckOrder\Actions\Backend\SaveHanoiCheckDeliveryAction;
use Modules\HanoiCheckOrder\Actions\Backend\SyncHanoiCheckOrderAction;
use Modules\HanoiCheckOrder\Data\Requests\LinkHanoiCheckOrderData;
use Modules\HanoiCheckOrder\Data\Requests\SaveHanoiCheckDeliveryData;
use Modules\HanoiCheckOrder\Enums\OrderSyncStatus;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\HanoiCheckOrder\Models\HanoiCheckTransporter;
use Modules\HanoiCheckOrder\Queries\ListHanoiCheckOrdersQuery;
use Modules\HanoiCheckOrder\Queries\ListRemoteHanoiCheckOrdersHandler;
use Modules\HanoiCheckOrder\Queries\ListRemoteHanoiCheckOrdersQuery;
use Modules\SalesOrder\Models\SalesOrder;
use Throwable;

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
            'syncStatuses' => collect(OrderSyncStatus::cases())->map(fn (OrderSyncStatus $s) => ['value' => $s->value, 'text' => $s->label()])->all(),
            'stats'    => ['linked' => $linked, 'total' => SalesOrder::query()->count()],
            'live'     => HanoiCheckClient::enabled() && ! HanoiCheckClient::fake(),
            'remoteUrl' => route('backend.hanoicheck-orders.remote'),
            'canManageTransporters' => request()->user()?->can('manageTransporters', HanoiCheckTransporter::class) ?? false,
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
                'sync_status'           => $link->fresh()->sync_status?->value,
                'sync_status_label'     => $link->fresh()->sync_status?->label(),
                'sync_status_badge'     => $link->fresh()->sync_status?->badge(),
            ] : null,
        ]);
    }

    public function detail(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('viewAny', HanoiCheckOrderLink::class);

        $link = HanoiCheckOrderLink::query()->where('sales_order_id', $salesOrder->id)->with('transporter')->firstOrFail();
        $target = SyncHanoiCheckOrderAction::targetFor($salesOrder);

        return response()->json([
            'order' => [
                'misa_ref_id'  => $salesOrder->misa_ref_id,
                'customer'     => $salesOrder->customer_name,
                'status_label' => SalesOrder::statusLabels()[$salesOrder->status] ?? $salesOrder->status,
                'target_label' => $target?->label(),
            ],
            'link' => [
                'code'               => $link->hanoicheck_order_code,
                'school_name'        => $link->school_name,
                'remote_status'      => $link->remote_status?->label(),
                'remote_badge'       => $link->remote_status?->badge(),
                'sync_status'        => $link->sync_status?->value,
                'sync_status_label'  => $link->sync_status?->label(),
                'sync_status_badge'  => $link->sync_status?->badge(),
                'sync_message'       => $link->sync_message,
                'fetched_at'         => $link->fetched_at?->format('H:i d/m/Y'),
                'synced_at'          => $link->synced_at?->format('H:i d/m/Y'),
                'transporter_id'     => $link->transporter_id,
                'photos'             => $link->photos(),
                'lines'              => $link->snapshot['lines'] ?? [],
                'leftover'           => $link->snapshot['leftover'] ?? [],
                'locked'             => $link->sync_status === OrderSyncStatus::Locked,
            ],
            'transporters' => HanoiCheckTransporter::query()->where('is_active', true)->orWhere('id', $link->transporter_id)->orderBy('name')
                ->get(['id', 'code', 'name', 'license_plate'])
                ->map(fn ($t) => ['value' => $t->id, 'text' => "{$t->name} · {$t->license_plate} ({$t->code})"])->all(),
            'logs' => HanoiCheckSyncLog::query()->where('entity_type', SyncEntity::Order->value)->where('local_id', $link->id)
                ->orderByDesc('created_at')->limit(10)->get()
                ->map(fn ($l) => [
                    'at'      => $l->created_at->format('H:i:s d/m/Y'),
                    'status'  => $l->status->value,
                    'path'    => $l->endpoint,
                    'http'    => $l->http_status,
                    'message' => $l->error_message,
                ])->all(),
            'live'         => HanoiCheckClient::enabled() && ! HanoiCheckClient::fake(),
            'delivery_url' => route('backend.hanoicheck-orders.delivery', $salesOrder),
            'sync_url'     => route('backend.hanoicheck-orders.sync', $salesOrder),
            'can_edit'     => request()->user()?->can('link', [HanoiCheckOrderLink::class, $salesOrder]) ?? false,
        ]);
    }

    public function delivery(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('link', [HanoiCheckOrderLink::class, $salesOrder]);

        $link = HanoiCheckOrderLink::query()->where('sales_order_id', $salesOrder->id)->firstOrFail();
        $data = SaveHanoiCheckDeliveryData::validateAndCreate([
            'transporter_id'   => $request->input('transporter_id') ?: null,
            'photos'           => $request->file('photos', []),
            'remove_photo_ids' => (array) $request->input('remove_photo_ids', []),
        ]);
        SaveHanoiCheckDeliveryAction::run($link, $data);

        return response()->json(['message' => 'Đã lưu thông tin giao hàng' . (HanoiCheckClient::enabled() ? ', đang đồng bộ lên HanoiCheck.' : '.')]);
    }

    public function sync(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('link', [HanoiCheckOrderLink::class, $salesOrder]);

        if (! HanoiCheckClient::enabled()) {
            return response()->json(['message' => 'Kết nối HanoiCheck chưa bật (HANOICHECK_ENABLED=false).'], 422);
        }

        $queued = QueueHanoiCheckOrderSyncAction::run($salesOrder->id, true);

        return response()->json(['message' => $queued ? 'Đã đưa đơn vào hàng đợi đồng bộ, kết quả sẽ hiện sau ít giây.' : 'Đơn đã chốt trên HanoiCheck hoặc chưa liên kết — không đồng bộ.']);
    }

    public function remote(Request $request, ListRemoteHanoiCheckOrdersHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', HanoiCheckOrderLink::class);

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'keyword'   => ['nullable', 'string', 'max:255'],
        ]);

        if (! HanoiCheckClient::enabled() || HanoiCheckClient::fake()) {
            return response()->json(['data' => [], 'message' => 'Chưa kết nối HanoiCheck thật — nhập mã đơn thủ công.']);
        }

        try {
            $rows = $handler->handle(new ListRemoteHanoiCheckOrdersQuery($validated['date_from'] ?? null, $validated['date_to'] ?? null, $validated['keyword'] ?? null));
        } catch (Throwable $e) {
            return response()->json(['data' => [], 'message' => 'Không tra được đơn trên HanoiCheck: ' . $e->getMessage()], 502);
        }

        return response()->json(['data' => $rows]);
    }
}
