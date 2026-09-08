<?php

namespace Modules\Sapo\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Sapo\Models\Customer;
use Modules\Sapo\Models\ExternalOrder;
use Modules\Sapo\Support\SapoOrderPayloadParser;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\RetailItemTag;
use Modules\Warehouse\Support\TraceabilityLinkBuilder;

class ProcessSapoOrderWebhookAction
{
    use AsAction;

    public function __construct(
        private readonly TraceabilityLinkBuilder $linkBuilder,
    ) {}

    public function handle(array $payload, string $rawBody): ExternalOrder
    {
        $parser = new SapoOrderPayloadParser($payload);

        return DB::transaction(function () use ($parser, $rawBody) {
            $customer = $this->upsertCustomer($parser);

            $order = ExternalOrder::updateOrCreate(
                [
                    'external_system'      => 'sapo',
                    'external_order_code'  => $parser->orderCode() ?? uniqid('sapo-', true),
                ],
                [
                    'customer_id'  => $customer?->id,
                    'ordered_at'   => $parser->orderedAt(),
                    'total_amount' => $parser->totalAmount(),
                    'status'       => 'received',
                    'raw_payload'  => $rawBody,
                ],
            );

            $this->markTagsSold($parser->serialCodes(), $order);

            $order->update(['status' => 'processed']);

            return $order;
        });
    }

    private function upsertCustomer(SapoOrderPayloadParser $parser): ?Customer
    {
        $phone = $parser->customerPhone();

        if (! $phone) {
            return null;
        }

        return Customer::updateOrCreate(
            ['phone' => $phone],
            [
                'name'                => $parser->customerName() ?? $phone,
                'email'               => $parser->customerEmail(),
                'external_system'     => 'sapo',
                'external_system_id'  => $parser->customerExternalId(),
            ],
        );
    }

    private function markTagsSold(array $scannedCodes, ExternalOrder $order): void
    {
        if (empty($scannedCodes)) {
            return;
        }

        $tags = collect();

        foreach ($scannedCodes as $code) {
            $tag = RetailItemTag::where('qr_code', $code)
                ->where('status', '!=', RetailItemTagStatus::Sold->value)
                ->first();

            if (! $tag && $parsed = $this->linkBuilder->parse($code)) {
                $tag = RetailItemTag::query()
                    ->whereHas('product', fn ($q) => $q->where('sku', $parsed['sku']))
                    ->whereHas('batch', fn ($q) => $q->where('internal_batch_code', $parsed['batch_code']))
                    ->where('serial_number', $parsed['serial'])
                    ->where('status', '!=', RetailItemTagStatus::Sold->value)
                    ->first();
            }

            if (! $tag && $uid = $this->linkBuilder->parseUid($code)) {
                $tag = RetailItemTag::query()
                    ->where('uid', $uid)
                    ->where('status', '!=', RetailItemTagStatus::Sold->value)
                    ->first();
            }

            if ($tag) {
                $tags->push($tag);
            }
        }

        foreach ($tags->unique('id') as $tag) {
            $tag->update([
                'status'            => RetailItemTagStatus::Sold->value,
                'sold_at'           => now(),
                'external_order_id' => $order->id,
            ]);

            Batch::whereKey($tag->batch_id)->where('current_qty', '>', 0)->decrement('current_qty');
        }
    }
}
