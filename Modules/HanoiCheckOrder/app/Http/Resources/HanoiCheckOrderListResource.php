<?php

namespace Modules\HanoiCheckOrder\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\SalesOrder\Models\SalesOrder;

class HanoiCheckOrderListResource extends JsonResource
{
    private const STATUS_BADGES = [
        SalesOrder::STATUS_PENDING   => 'badge-ghost',
        SalesOrder::STATUS_SHIPPING  => 'badge-info',
        SalesOrder::STATUS_DELIVERED => 'badge-success',
    ];

    public function toArray(Request $request): array
    {
        $linkedAt = $this->hanoicheck_linked_at ? Carbon::parse($this->hanoicheck_linked_at) : null;

        return [
            'id'                    => $this->id,
            'misa_ref_id'           => $this->misa_ref_id,
            'order_url'             => route('backend.sales-orders.show', $this->resource),
            'customer_name'         => $this->customer_name,
            'delivery_date'         => $this->delivery_date?->format('d/m/Y'),
            'status'                => $this->status,
            'status_label'          => SalesOrder::statusLabels()[$this->status] ?? $this->status,
            'status_badge'          => self::STATUS_BADGES[$this->status] ?? 'badge-ghost',
            'hanoicheck_order_code' => $this->hanoicheck_order_code,
            'note'                  => $this->hanoicheck_note,
            'linked_at'             => $linkedAt?->format('H:i d/m/Y'),
            'linked_by'             => $this->hanoicheck_linked_by_name,
            'link_url'              => route('backend.hanoicheck-orders.link', $this->resource),
            'can_link'              => $request->user()?->can('link', [HanoiCheckOrderLink::class, $this->resource]) ?? false,
        ];
    }
}
