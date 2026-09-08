<?php

namespace Modules\Recall\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Sapo\Models\ExternalOrder;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\RetailItemTag;

class GetRecallAffectedCustomersHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var GetRecallAffectedCustomersQuery $query */
        $recall = $query->recall;

        $tagsQuery = RetailItemTag::query()
            ->where('product_id', $recall->product_id)
            ->where('status', RetailItemTagStatus::Sold->value)
            ->whereNotNull('external_order_id');

        if ($recall->batch_id) {
            $tagsQuery->where('batch_id', $recall->batch_id);
        }

        $tags = $tagsQuery->get();

        $orders = ExternalOrder::withoutTenant()
            ->whereIn('id', $tags->pluck('external_order_id')->unique())
            ->with('customer')
            ->get()
            ->keyBy('id');

        return $tags
            ->map(function (RetailItemTag $tag) use ($orders) {
                $order = $orders->get($tag->external_order_id);

                return [
                    'qr_code'        => $tag->qr_code,
                    'sold_at'        => $tag->sold_at,
                    'order_code'     => $order?->external_order_code,
                    'customer_name'  => $order?->customer?->name,
                    'customer_phone' => $order?->customer?->phone,
                ];
            })
            ->filter(fn (array $row) => $row['customer_phone'] !== null)
            ->values();
    }
}
