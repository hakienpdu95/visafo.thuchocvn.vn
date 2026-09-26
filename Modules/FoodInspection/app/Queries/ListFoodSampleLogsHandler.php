<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\FoodInspection\Enums\SampleStatus;
use Modules\FoodInspection\Models\FoodSampleDetail;
use Modules\FoodInspection\Models\FoodSampleLog;

class ListFoodSampleLogsHandler implements QueryHandlerInterface
{
    /** Trạng thái lọc thêm "pending" = còn mẫu đã đủ 24 giờ nhưng chưa hủy (Chờ hủy mẫu). */
    public const STATUS_PENDING = 'pending';

    private const SORTABLE = ['sample_date', 'customer_name', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListFoodSampleLogsQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'sample_date';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';
        $threshold = now()->subHours(FoodSampleDetail::MIN_RETENTION_HOURS);
        $due = fn (Builder $d) => $d->whereNull('destroyed_at')->where('sampled_at', '<=', $threshold);

        $q = FoodSampleLog::query()->visibleTo()
            ->withCount('details')
            ->withExists(['details as has_pending' => $due])
            ->withMin(['details as first_sampled_at' => fn (Builder $d) => $d->whereNull('destroyed_at')], 'sampled_at');

        if ($query->search) {
            $term = '%' . $query->search . '%';
            $q->where(fn (Builder $s) => $s->where('customer_name', 'like', $term)->orWhere('location_name', 'like', $term)
                ->orWhereHas('details', fn (Builder $d) => $d->where('dish_name', 'like', $term)));
        }
        if ($query->customerId) {
            $q->where('customer_id', $query->customerId);
        }
        if ($query->status === SampleStatus::Destroyed->value) {
            $q->where('status', SampleStatus::Destroyed);
        } elseif ($query->status === self::STATUS_PENDING) {
            $q->where('status', SampleStatus::Stored)->whereHas('details', $due);
        } elseif ($query->status === SampleStatus::Stored->value) {
            $q->where('status', SampleStatus::Stored)->whereDoesntHave('details', $due);
        }
        if ($query->dateFrom) {
            $q->whereDate('sample_date', '>=', $query->dateFrom);
        }
        if ($query->dateTo) {
            $q->whereDate('sample_date', '<=', $query->dateTo);
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'sample_date') {
            $q->orderByDesc('sample_date');
        }

        return $q->orderBy('id', $sortDir)->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
