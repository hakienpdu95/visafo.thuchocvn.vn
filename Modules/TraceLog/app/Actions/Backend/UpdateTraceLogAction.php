<?php

namespace Modules\TraceLog\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\ActivityLog\Enums\LogLevel;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Support\PrintSourceResolver;
use Modules\TraceLog\Data\Requests\UpdateTraceLogData;

class UpdateTraceLogAction
{
    use AsAction;

    private const TRACKED = ['mfg_date', 'exp_date', 'batch_code', 'product_batch_id', 'vendor_id', 'partner_product_id', 'supplier_name'];

    public function __construct(private readonly PrintSourceResolver $sourceResolver) {}

    public function handle(PrintLog $printLog, UpdateTraceLogData $data, ?string $userId): int
    {
        return DB::transaction(function () use ($printLog, $data, $userId) {
            /** @var PrintLog $log */
            $log = PrintLog::query()->with('orderItem.product')->lockForUpdate()->findOrFail($printLog->id);

            if ($log->status !== PrintLogStatus::Active) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ sửa được tem đang lưu hành (tem này: ' . $log->status->label() . ').',
                ]);
            }

            $source = $this->sourceResolver->resolve([
                'product_batch_id'   => $data->product_batch_id,
                'vendor_id'          => $data->vendor_id,
                'partner_product_id' => $data->partner_product_id,
                'supplier_name'      => $data->supplier_name,
            ], $log->orderItem);

            $before = $this->snapshot($log);
            $values = [
                'mfg_date'           => $data->mfg_date,
                'exp_date'           => $data->exp_date,
                'batch_code'         => trim((string) $data->batch_code) ?: null,
                'product_batch_id'   => $source['product_batch_id'],
                'vendor_id'          => $source['vendor_id'],
                'partner_product_id' => $source['partner_product_id'] ?? null,
                'supplier_name'      => $source['supplier_name'],
            ];

            $count = ($data->update_all_related ? self::relatedActive($log) : PrintLog::query()->whereKey($log->id))->update($values);

            $after = $this->snapshot($log->fresh());
            $context = ['trace_code' => strtoupper($log->trace_code), 'updated_by' => $userId];
            foreach (self::TRACKED as $key) {
                if ($before[$key] !== $after[$key]) {
                    $context["old_{$key}"] = $before[$key];
                    $context["new_{$key}"] = $after[$key];
                }
            }

            if ($data->update_all_related) {
                $productName = $log->orderItem?->product?->name ?? $log->orderItem?->product_name_raw;
                ActivityLogger::log('TraceLog', 'trace_log_bulk_updated', $log, $context + [
                    'order_item_id' => $log->order_item_id,
                    'updated_count' => $count,
                    'new_vendor_id' => $after['vendor_id'],
                    'new_product_batch_id' => $after['product_batch_id'],
                ], LogLevel::Warning,
                    "Đã cập nhật Nguồn cung/Lô hàng loạt cho {$count} tem của mặt hàng {$productName} (Order Item ID: {$log->order_item_id}). ID Nguồn mới: " . ($after['vendor_id'] ?? $after['supplier_name'] ?? '—'));
            } elseif (count($context) > 2) {
                ActivityLogger::log('TraceLog', 'trace_log_updated', $log, $context, LogLevel::Warning,
                    'Sửa thông tin TXNG tem ' . strtoupper($log->trace_code));
            }

            return $count;
        });
    }

    public static function relatedActive(PrintLog $log)
    {
        return PrintLog::query()
            ->where('order_item_id', $log->order_item_id)
            ->where('status', PrintLogStatus::Active);
    }

    private function snapshot(PrintLog $log): array
    {
        return [
            'mfg_date'           => $log->mfg_date?->format('Y-m-d'),
            'exp_date'           => $log->exp_date?->format('Y-m-d'),
            'batch_code'         => $log->batch_code,
            'product_batch_id'   => $log->product_batch_id,
            'vendor_id'          => $log->vendor_id,
            'partner_product_id' => $log->partner_product_id,
            'supplier_name'      => $log->supplier_name,
        ];
    }
}
