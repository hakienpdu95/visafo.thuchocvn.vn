<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\TagRoll;

class GetSerialLifecycleHandler implements QueryHandlerInterface
{
    /**
     * Dựng lại các mốc đáng tin cậy trong vòng đời của 1 tem, dựa trên dữ liệu
     * thực sự lưu lại được (created_at của cuộn, sold_at, external_order — đều
     * là các cột riêng không bị ghi đè). Mốc "gắn kết/kích hoạt" chỉ có ngày
     * chính xác nếu đó là thao tác GẦN NHẤT (updated_at) — nếu tem đã có thao
     * tác mới hơn (VD: đã bán), ngày gắn kết cũ không còn lưu lại được vì
     * retail_item_tags không có bảng lịch sử riêng cho từng lần đổi trạng thái.
     *
     * @return array<int, array{label: string, date: ?\Illuminate\Support\Carbon, detail: string, certain: bool}>
     */
    public function handle(QueryInterface $query): array
    {
        /** @var GetSerialLifecycleQuery $query */
        $tag = $query->tag;

        $events = [];

        $events[] = $this->provisioningEvent($tag);

        if ($tag->batch) {
            $events[] = $this->bindingEvent($tag);
        }

        if ($tag->sold_at || $tag->externalOrder) {
            $events[] = $this->soldEvent($tag);
        }

        $events[] = $this->currentStatusEvent($tag);

        return array_values(array_filter($events));
    }

    private function provisioningEvent($tag): array
    {
        if ($tag->visual_sequence !== null) {
            $roll = TagRoll::where('from_sequence', '<=', $tag->visual_sequence)
                ->where('to_sequence', '>=', $tag->visual_sequence)
                ->first();

            if ($roll) {
                return [
                    'label'   => 'Khởi tạo (in tem)',
                    'date'    => $roll->created_at,
                    'detail'  => "Cuộn tem \"{$roll->prefix}\" — visual_sequence {$tag->visual_sequence}",
                    'certain' => true,
                ];
            }
        }

        return [
            'label'   => 'Khởi tạo (in tem)',
            'date'    => $tag->created_at,
            'detail'  => 'Tem sinh trực tiếp khi hoàn tất phiếu nhập kho (chưa qua kho tem tiền định danh)',
            'certain' => true,
        ];
    }

    private function bindingEvent($tag): array
    {
        $stillFreshestChange = in_array($tag->status, [
            RetailItemTagStatus::Bound,
            RetailItemTagStatus::InStock,
        ], true);

        return [
            'label'   => 'Gắn kết vào lô hàng',
            'date'    => $stillFreshestChange ? $tag->updated_at : null,
            'detail'  => "Lô \"{$tag->batch->internal_batch_code}\" — {$tag->product?->name}"
                . ($stillFreshestChange ? '' : ' (ngày chính xác không còn lưu lại do đã có thao tác mới hơn)'),
            'certain' => $stillFreshestChange,
        ];
    }

    private function soldEvent($tag): array
    {
        $order = $tag->externalOrder;

        $detail = $order
            ? "Đơn hàng Sapo \"{$order->external_order_code}\""
                . ($order->customer ? " — KH: {$order->customer->name}" . ($order->customer->phone ? " ({$order->customer->phone})" : '') : '')
            : 'Đã xuất bán (không có thông tin đơn hàng đối soát)';

        return [
            'label'   => 'Xuất bán qua Sapo',
            'date'    => $tag->sold_at,
            'detail'  => $detail,
            'certain' => (bool) $tag->sold_at,
        ];
    }

    private function currentStatusEvent($tag): array
    {
        return [
            'label'   => 'Trạng thái hiện tại',
            'date'    => $tag->updated_at,
            'detail'  => $tag->status->label() . ' (cập nhật lần cuối)',
            'certain' => true,
        ];
    }
}
