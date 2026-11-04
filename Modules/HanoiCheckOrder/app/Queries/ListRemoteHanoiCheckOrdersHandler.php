<?php

namespace Modules\HanoiCheckOrder\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheckOrder\Enums\RemoteOrderStatus;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;

class ListRemoteHanoiCheckOrdersHandler implements QueryHandlerInterface
{
    public function __construct(private readonly HanoiCheckClient $client) {}

    public function handle(QueryInterface $query): array
    {
        $params = array_filter([
            'order_date_from' => $query->dateFrom,
            'order_date_to'   => $query->dateTo,
            'keyword'         => $query->keyword,
            'per_page'        => min(100, max(1, $query->perPage)),
            'page'            => 1,
        ], fn ($v) => filled($v));

        $rows = collect($this->client->get('/api/supplier/orders', $params)['json']['data'] ?? []);
        $linked = HanoiCheckOrderLink::query()
            ->whereIn('hanoicheck_order_code', $rows->pluck('code')->filter())
            ->with('salesOrder:id,misa_ref_id')
            ->get()->keyBy('hanoicheck_order_code');

        return $rows->map(function (array $row) use ($linked) {
            $status = RemoteOrderStatus::tryFrom((string) ($row['status'] ?? ''));

            return [
                'code'         => $row['code'] ?? null,
                'school_name'  => $row['school']['name'] ?? null,
                'order_date'   => $row['order_date'] ?? $row['delivery_date'] ?? null,
                'status_label' => $status?->label() ?? ($row['status'] ?? null),
                'products'     => collect($row['products'] ?? [])->map(fn ($p) => $p['name'] ?? $p['code'] ?? '')->filter()->values()->all(),
                'linked_to'    => $linked->get($row['code'] ?? '')?->salesOrder?->misa_ref_id,
            ];
        })->filter(fn ($r) => filled($r['code']))->values()->all();
    }
}
