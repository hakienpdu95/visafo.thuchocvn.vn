<?php

namespace Modules\HanoiCheckOrder\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\HanoiCheckOrder\Data\Requests\LinkHanoiCheckOrderData;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\SalesOrder\Models\SalesOrder;

class LinkHanoiCheckOrderAction
{
    use AsAction;

    public function handle(SalesOrder $order, LinkHanoiCheckOrderData $data, ?string $userId): ?HanoiCheckOrderLink
    {
        $code = trim((string) $data->hanoicheck_order_code) ?: null;

        return DB::transaction(function () use ($order, $data, $code, $userId) {
            $link = HanoiCheckOrderLink::query()->where('sales_order_id', $order->id)->lockForUpdate()->first();

            if ($code === null) {
                $link?->delete();

                return null;
            }

            $taken = HanoiCheckOrderLink::query()
                ->where('hanoicheck_order_code', $code)
                ->where('sales_order_id', '!=', $order->id)
                ->with('salesOrder:id,misa_ref_id')
                ->first();
            if ($taken !== null) {
                throw ValidationException::withMessages([
                    'hanoicheck_order_code' => "Mã đơn HanoiCheck \"{$code}\" đã được liên kết với đơn " . ($taken->salesOrder?->misa_ref_id ?? $taken->sales_order_id) . '.',
                ]);
            }

            $link ??= new HanoiCheckOrderLink(['sales_order_id' => $order->id]);
            $changed = $link->hanoicheck_order_code !== $code;
            $link->fill([
                'hanoicheck_order_code' => $code,
                'note'                  => trim((string) $data->note) ?: null,
            ]);
            if ($changed) {
                $link->linked_by = $userId;
                $link->linked_at = now();
            }
            $link->save();

            return $link;
        });
    }
}
