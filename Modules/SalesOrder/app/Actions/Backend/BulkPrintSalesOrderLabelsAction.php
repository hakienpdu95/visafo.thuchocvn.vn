<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\LabelPrintEvent;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\PrintLogAttribute;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\BatchAttributeResolver;
use Modules\SalesOrder\Support\PrintSourceResolver;

class BulkPrintSalesOrderLabelsAction
{
    use AsAction;

    public function __construct(
        private readonly BatchAttributeResolver $resolver,
        private readonly PrintSourceResolver $sourceResolver,
    ) {}

    public function handle(SalesOrder $order, array $data, ?string $printedBy): array
    {
        $resolver = $this->resolver;
        $sourceResolver = $this->sourceResolver;

        return DB::transaction(function () use ($order, $data, $printedBy, $resolver, $sourceResolver) {
            // Khóa các dòng hàng của đơn: 2 lượt in toàn bộ đơn chạy song song không sinh trùng mã
            $items = SalesOrderItem::query()
                ->where('order_id', $order->id)
                ->with('product')
                ->orderBy('line_no')
                ->lockForUpdate()
                ->get();

            $sessionId = Str::lower((string) Str::ulid());
            $logs = [];
            $total = 0;
            $printedItems = 0;
            $reusedItems = 0;
            $sourceChanged = 0;

            $customGroups = isset($data['items'])
                ? collect($data['items'])->mapWithKeys(fn ($i) => [$i['order_item_id'] => collect($i['label_groups'])
                    ->map(fn ($g) => ['weight' => round((float) $g['weight_per_label'], 3), 'qty' => (int) $g['label_count']])
                    ->all()])
                : null;
            $itemSources = collect($data['items'] ?? [])->keyBy('order_item_id');

            foreach ($items as $item) {
                if ($customGroups !== null) {
                    if (! $customGroups->has($item->id)) {
                        continue;
                    }

                    $groups = $customGroups->get($item->id);
                } else {
                    $weight = round((float) $item->requested_qty, 3);
                    if ($weight <= 0) {
                        continue;
                    }

                    $groups = [['weight' => $weight, 'qty' => 1]];
                }

                $total++;
                $source = $sourceResolver->forBulkItem($item, $itemSources->get($item->id, []), $data);

                // Check & Reuse (xem PrintSalesOrderItemLabelAction): đã có tem đang lưu hành → in lại mã cũ
                $existing = PrintLog::query()->activeForItem($item->id)->get();
                if ($existing->isNotEmpty()) {
                    if (PrintLog::sourceDiffers($existing, $source)) {
                        PrintLog::updateSource($existing, $source);
                        $sourceChanged++;
                    }
                    $existing->each(fn (PrintLog $log) => $log->markReprinted($sessionId));
                    LabelPrintEvent::record($existing, $sessionId, $printedBy, true);
                    array_push($logs, ...$existing->all());
                    $printedItems++;
                    $reusedItems++;

                    continue;
                }

                $attributes = $resolver->forItem($item)['attributes'];
                $created = [];

                foreach ($groups as $group) {
                    for ($i = 0; $i < $group['qty']; $i++) {
                        $log = PrintLog::create([
                            'print_session_id'  => $sessionId,
                            'order_item_id'     => $item->id,
                            'label_template_id' => $data['label_template_id'] ?? null,
                            'weight_per_label'  => $group['weight'],
                            'mfg_date'          => $data['mfg_date'] ?? null,
                            'exp_date'          => $data['exp_date'],
                            'supplier_name'     => $source['supplier_name'],
                            'vendor_id'         => $source['vendor_id'],
                            'product_batch_id'  => $source['product_batch_id'],
                            'batch_code'        => $data['batch_code'] ?? null,
                            'printed_by'        => $printedBy,
                            'status'            => PrintLogStatus::Active,
                        ]);

                        foreach ($attributes as $attr) {
                            PrintLogAttribute::create([
                                'print_log_id'    => $log->id,
                                'attribute_key'   => $attr['key'],
                                'attribute_value' => $attr['value'],
                            ]);
                        }

                        $logs[] = $log;
                        $created[] = $log;
                    }
                }

                LabelPrintEvent::record($created, $sessionId, $printedBy, false);

                $printedItems++;
            }

            return [
                'session_id' => $sessionId,
                'logs'    => $logs,
                'total'   => $total,
                'printed' => $printedItems,
                'reused'  => $reusedItems,
                'source_changed' => $sourceChanged,
            ];
        });
    }
}
