<?php

namespace Modules\HanoiCheckOrder\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\HanoiCheck\Services\HanoiCheckClient;

class GetRemoteHanoiCheckOrderHandler implements QueryHandlerInterface
{
    public function __construct(private readonly HanoiCheckClient $client) {}

    public function handle(QueryInterface $query): array
    {
        $code = rawurlencode($query->code);
        $detail = $this->client->get("/api/supplier/orders/{$code}")['json']['data'] ?? [];
        $options = $this->client->get("/api/supplier/orders/{$code}/status-options")['json']['data'] ?? [];

        $lines = [];
        foreach ($detail['items'] ?? [] as $item) {
            $trace = (string) ($item['trace_code'] ?? '');
            if ($trace === '') {
                continue;
            }

            $fulfillment = $this->client->get("/api/supplier/orders/{$code}/items/" . rawurlencode($trace) . '/fulfillment-options')['json']['data'] ?? [];
            $food = $fulfillment['food'] ?? null;

            $lines[] = [
                'trace_code'       => $trace,
                'update_mode'      => $fulfillment['update_mode'] ?? null,
                'product_type'     => $fulfillment['product_type'] ?? null,
                'requested_amount' => (float) ($fulfillment['requested_amount'] ?? 0),
                'food_code'        => $food['code'] ?? ($item['code'] ?? null),
                'food_name'        => $food['name'] ?? ($item['name'] ?? null),
                'batches'          => collect($food['batches'] ?? [])->map(fn ($b) => [
                    'code'       => $b['code'] ?? null,
                    'warehouses' => collect($b['warehouses'] ?? [])->map(fn ($w) => ['code' => $w['code'] ?? null])->filter(fn ($w) => $w['code'] !== null)->values()->all(),
                ])->filter(fn ($b) => $b['code'] !== null)->values()->all(),
                'allocations'      => $item['allocations'] ?? [],
            ];
        }

        return [
            'code'           => $query->code,
            'school_name'    => $detail['school']['name'] ?? null,
            'status'         => $options['current_status']['value'] ?? ($detail['status'] ?? null),
            'status_options' => collect($options['status_options'] ?? [])->pluck('value')->filter()->values()->all(),
            'transporter'    => $detail['transporter']['code'] ?? null,
            'photo_count'    => count($detail['images'] ?? $detail['danh_sach_anh'] ?? []),
            'lines'          => $lines,
        ];
    }
}
