<?php

namespace Modules\TraceLog\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\SalesOrder\Models\PrintLog;

class ListTraceLogsHandler implements QueryHandlerInterface
{
    public const VENDOR_NONE = '__none';

    private const SORTABLE = ['created_at', 'last_printed_at', 'trace_code', 'weight_per_label', 'status'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListTraceLogsQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'last_printed_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = PrintLog::query()->visibleTo()->with(['orderItem.product', 'orderItem.salesOrder', 'printedBy:id,name', 'productBatch:id,batch_code']);

        $search = $this->normalizeSearch($query->search);
        if ($search !== null) {
            $term = '%' . $search . '%';
            $q->where(function (Builder $sub) use ($search, $term): void {
                $sub->where('trace_code', 'like', $search . '%')
                    ->orWhereHas('orderItem.product', fn (Builder $p) => $p->where('name', 'like', $term)->orWhere('sku', 'like', $term))
                    ->orWhereHas('orderItem.salesOrder', fn (Builder $o) => $o->where('misa_ref_id', 'like', $term));
            });
        }

        if ($query->customer !== null && $query->customer !== '') {
            $q->whereHas('orderItem.salesOrder', fn (Builder $o) => $o->where('customer_name', $query->customer));
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('status', $query->status);
        }

        $dateFrom = ($query->dateFrom ?? '') !== '' ? Carbon::parse($query->dateFrom)->startOfDay() : null;
        $dateTo = ($query->dateTo ?? '') !== '' ? Carbon::parse($query->dateTo)->endOfDay() : null;

        if ($dateFrom !== null || $dateTo !== null) {
            $q->whereHas('printEvents', function (Builder $e) use ($dateFrom, $dateTo): void {
                $e->when($dateFrom, fn (Builder $b) => $b->where('printed_at', '>=', $dateFrom))
                    ->when($dateTo, fn (Builder $b) => $b->where('printed_at', '<=', $dateTo));
            });
        }

        if ($query->vendor === self::VENDOR_NONE) {
            $q->whereNull('vendor_id');
        } elseif ($query->vendor !== null && $query->vendor !== '') {
            $q->where('vendor_id', $query->vendor);
        }

        if ($query->batch !== null && trim($query->batch) !== '') {
            $batch = trim($query->batch);
            $q->where(function (Builder $sub) use ($batch): void {
                $sub->where('batch_code', 'like', $batch . '%')
                    ->orWhereHas('productBatch', fn (Builder $b) => $b->where('batch_code', 'like', $batch . '%'));
            });
        }

        $q->orderBy($sortField, $sortDir)->orderBy('id', $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }

    /**
     * Súng quét QR gửi nguyên URL (…/trace/abc123def4) → chỉ lấy mã truy xuất.
     */
    private function normalizeSearch(?string $search): ?string
    {
        $search = trim((string) $search);
        if ($search === '') {
            return null;
        }

        if (preg_match('~/trace/([A-Za-z0-9]{8,32})~', $search, $m)) {
            return strtolower($m[1]);
        }

        return $search;
    }
}
