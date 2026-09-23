<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\PrintLogAttribute;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\BatchAttributeResolver;

class BulkPrintSalesOrderLabelsAction
{
    use AsAction;

    public function __construct(private readonly BatchAttributeResolver $resolver) {}

    public function handle(SalesOrder $order, array $data, ?string $printedBy): array
    {
        $resolver = $this->resolver;

        return DB::transaction(function () use ($order, $data, $printedBy, $resolver) {
            $items = SalesOrderItem::query()
                ->where('order_id', $order->id)
                ->with('product')
                ->orderBy('line_no')
                    ->get();

            $sessionId = Str::lower((string) Str::ulid());
            $logs = [];
            $total = 0;
            $printedItems = 0;

            $customGroups = isset($data['items'])
                ? collect($data['items'])->mapWithKeys(fn ($i) => [$i['order_item_id'] => collect($i['label_groups'])
                    ->map(fn ($g) => ['weight' => round((float) $g['weight_per_label'], 3), 'qty' => (int) $g['label_count']])
                    ->all()])
                : null;

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
                $attributes = $resolver->forItem($item)['attributes'];

                foreach ($groups as $group) {
                    for ($i = 0; $i < $group['qty']; $i++) {
                        $log = PrintLog::create([
                            'print_session_id'  => $sessionId,
                            'order_item_id'     => $item->id,
                            'label_template_id' => $data['label_template_id'] ?? null,
                            'weight_per_label'  => $group['weight'],
                            'mfg_date'          => $data['mfg_date'] ?? null,
                            'exp_date'          => $data['exp_date'],
                            'supplier_name'     => $data['supplier_name'] ?? null,
                            'vendor_id'         => $data['vendor_id'] ?? null,
                            'batch_code'        => $data['batch_code'] ?? null,
                            'printed_by'        => $printedBy,
                        ]);

                        foreach ($attributes as $attr) {
                            PrintLogAttribute::create([
                                'print_log_id'    => $log->id,
                                'attribute_key'   => $attr['key'],
                                'attribute_value' => $attr['value'],
                            ]);
                        }

                        $logs[] = $log;
                    }
                }

                $printedItems++;
            }

            return [
                'session_id' => $sessionId,
                'logs'    => $logs,
                'total'   => $total,
                'printed' => $printedItems,
            ];
        });
    }
}
