<?php

namespace Modules\TraceLog\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;

class GetTraceLogDetailHandler implements QueryHandlerInterface
{
    /** @return array<string, mixed> Hồ sơ đầy đủ của một tem, dùng cho modal "Xem chi tiết". */
    public function handle(QueryInterface $query): array
    {
        /** @var GetTraceLogDetailQuery $query */
        $log = $query->printLog->load([
            'attributes', 'labelTemplate', 'printedBy:id,name', 'statusChangedBy:id,name',
            'orderItem.product', 'orderItem.salesOrder',
        ]);

        $item = $log->orderItem;
        $order = $item?->salesOrder;

        return [
            'id'          => $log->id,
            'trace_code'  => $log->trace_code,
            'status'      => $log->status->value,
            'status_label' => $log->status->label(),
            'status_badge' => $log->status->badgeClass(),
            'status_reason' => $log->status_reason,
            'status_changed_at' => $log->status_changed_at?->format('d/m/Y H:i'),
            'status_changed_by' => $log->statusChangedBy?->name,

            'product'     => ['name' => $item?->product?->name ?? $item?->product_name_raw, 'sku' => $item?->product?->sku],
            'order'       => $order ? [
                'misa_ref_id'      => $order->misa_ref_id,
                'url'              => route('backend.sales-orders.show', $order),
                'customer_name'    => $order->customer_name,
                'delivery_address' => $order->delivery_address,
            ] : null,

            'weight'      => number_format((float) $log->weight_per_label, 3) . ' kg',
            'mfg_date'    => $log->mfg_date?->format('d/m/Y'),
            'exp_date'    => $log->exp_date?->format('d/m/Y'),
            'supplier_name' => $log->supplier_name,
            'template'    => $log->labelTemplate?->name,
            'attributes'  => $log->attributes
                ->map(fn ($a) => ['key' => $a->attribute_key, 'value' => (string) $a->attribute_value])->values()->all(),

            'printed_at'  => $log->created_at?->format('d/m/Y H:i'),
            'printed_by'  => $log->printedBy?->name,
            'session_count' => $log->print_session_id
                ? PrintLog::query()->where('print_session_id', $log->print_session_id)->count()
                : 1,

            'location'    => $this->location($order),

            'preview_url' => route('backend.trace-logs.preview', $log),
            'public_url'  => route('trace.show', $log->trace_code),
            'status_url'  => route('backend.trace-logs.status', $log),
        ];
    }

    /** Vị trí vật lý hiện tại của tem dựa trên trạng thái đơn xuất hàng. */
    private function location(?SalesOrder $order): array
    {
        if ($order === null) {
            return ['label' => 'Chưa xác định', 'detail' => null];
        }

        if ($order->status === 'pending') {
            return ['label' => 'Đang ở kho', 'detail' => 'Đơn xuất hàng chờ xuất kho / giao hàng.'];
        }

        return [
            'label'  => 'Đã xuất kho — đang giao / đã giao',
            'detail' => $order->delivery_address ? 'Điểm giao: ' . $order->delivery_address : null,
        ];
    }
}
