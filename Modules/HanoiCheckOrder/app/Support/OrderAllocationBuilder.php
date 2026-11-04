<?php

namespace Modules\HanoiCheckOrder\Support;

use Illuminate\Support\Collection;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Support\HanoiCheckCodes;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;

class OrderAllocationBuilder
{
    public const MODE_FOOD = 'food_fulfillment';

    public function build(SalesOrder $order, array $lines): array
    {
        $tolerance = (float) config('services.hanoicheck.allocation_tolerance', 0);
        $defaultWarehouse = (string) config('services.hanoicheck.defaults.ma_kho');
        $pools = $this->pools($order);
        $result = [];

        foreach ($lines as $line) {
            $row = [
                'trace_code'       => $line['trace_code'],
                'food_code'        => $line['food_code'],
                'food_name'        => $line['food_name'] ?? null,
                'requested_amount' => (float) $line['requested_amount'],
                'allocated_amount' => 0.0,
                'phan_bo'          => [],
                'batch_ids'        => [],
                'error'            => null,
            ];

            if (($line['update_mode'] ?? self::MODE_FOOD) !== self::MODE_FOOD) {
                $row['error'] = 'Dòng suất ăn / thực đơn: VISAFO chưa hỗ trợ khai nguyên liệu theo món.';
                $result[] = $row;

                continue;
            }

            $pool = $pools[$line['food_code']] ?? null;
            if ($pool === null) {
                $row['error'] = "Đơn VISAFO không có mặt hàng mã {$line['food_code']} hoặc chưa in tem cho mặt hàng này.";
                $result[] = $row;

                continue;
            }

            $need = $row['requested_amount'];
            $remoteBatches = collect($line['batches'] ?? [])->keyBy('code');

            foreach ($pool['batches'] as $code => &$batch) {
                if ($need <= 1e-9) {
                    break;
                }
                if ($batch['available'] <= 1e-9) {
                    continue;
                }

                $remote = $remoteBatches->get($code);
                if ($remote === null) {
                    $row['error'] ??= "Lô {$code} chưa có trên HanoiCheck cho thực phẩm này (đang đồng bộ lô, thử lại sau ít phút).";
                    $row['batch_ids'][] = $batch['id'];

                    continue;
                }

                $warehouses = collect($remote['warehouses'] ?? [])->pluck('code');
                $warehouse = $warehouses->contains($defaultWarehouse) ? $defaultWarehouse : $warehouses->first();
                if ($warehouse === null) {
                    $row['error'] ??= "Lô {$code} chưa được gán vào kho trên HanoiCheck.";

                    continue;
                }

                $take = min($need, $batch['available']);
                $batch['available'] -= $take;
                $need -= $take;
                $row['phan_bo'][] = ['ma_lo' => $code, 'ma_kho' => $warehouse, 'so_luong' => $take];
                $row['batch_ids'][] = $batch['id'];
            }
            unset($batch);

            if ($need > 1e-9 && $need <= $tolerance && $row['phan_bo'] !== []) {
                $row['phan_bo'][array_key_last($row['phan_bo'])]['so_luong'] += $need;
                $need = 0;
            }

            $row['allocated_amount'] = round(array_sum(array_column($row['phan_bo'], 'so_luong')), 5);
            if ($need > 1e-9) {
                $unbatched = $pool['unbatched'] > 0 ? ' (' . self::format($pool['unbatched']) . ' trên tem chưa gắn lô nhập)' : '';
                $row['error'] ??= 'Thiếu ' . self::format($need) . ' so với số lượng trường đặt ' . self::format($row['requested_amount']) . $unbatched . '.';
            }

            $row['phan_bo'] = array_map(fn ($p) => ['ma_lo' => $p['ma_lo'], 'ma_kho' => $p['ma_kho'], 'so_luong' => self::format($p['so_luong'])], $row['phan_bo']);
            $row['batch_ids'] = array_values(array_unique($row['batch_ids']));
            $result[] = $row;
        }

        $leftover = collect($pools)->map(fn ($p) => collect($p['batches'])->sum('available'))->filter(fn ($v) => $v > max($tolerance, 1e-6));

        return [
            'lines'    => $result,
            'complete' => $result !== [] && collect($result)->every(fn ($r) => $r['error'] === null),
            'leftover' => $leftover->map(fn ($v) => self::format($v))->all(),
            'batch_ids' => collect($result)->pluck('batch_ids')->flatten()->unique()->values()->all(),
        ];
    }

    public static function format(float $value): string
    {
        $text = rtrim(rtrim(number_format(round($value, 5), 5, '.', ''), '0'), '.');

        return $text === '' || $text === '-0' ? '0' : $text;
    }

    private function pools(SalesOrder $order): array
    {
        $logs = PrintLog::query()
            ->whereIn('order_item_id', $order->items()->pluck('id'))
            ->where('status', PrintLogStatus::Active->value)
            ->with(['orderItem.product', 'productBatch'])
            ->orderBy('created_at')->orderBy('id')
            ->get();

        return $logs->toBase()->groupBy(fn (PrintLog $log) => $log->orderItem?->product ? HanoiCheckCodes::for(SyncEntity::Product, $log->orderItem->product) : '')
            ->except([''])
            ->map(fn (Collection $group) => [
                'unbatched' => (float) $group->whereNull('product_batch_id')->sum('weight_per_label'),
                'batches'   => $group->whereNotNull('product_batch_id')
                    ->groupBy('product_batch_id')
                    ->mapWithKeys(fn (Collection $g) => [
                        HanoiCheckCodes::for(SyncEntity::ProductBatch, $g->first()->productBatch ?? new ProductBatch(['batch_code' => $g->first()->product_batch_id])) => [
                            'id'        => $g->first()->product_batch_id,
                            'available' => (float) $g->sum('weight_per_label'),
                        ],
                    ])->all(),
            ])->all();
    }
}
