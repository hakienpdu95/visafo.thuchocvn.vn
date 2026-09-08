<?php

namespace Modules\Compliance\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Compliance\Enums\WarningStatus;
use Modules\Compliance\Models\ComplianceWarning;

class ListWarningsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListWarningsQuery $query */
        $q = ComplianceWarning::query()->with('acknowledgedBy');

        if ($query->status !== null && $query->status !== '') {
            $q->where('status', $query->status);
        } else {
            $q->whereIn('status', [WarningStatus::Pending->value, WarningStatus::Acknowledged->value]);
        }

        if ($query->category !== null && $query->category !== '') {
            $q->where('category', $query->category);
        }

        if ($query->severity !== null && $query->severity !== '') {
            $q->where('severity', $query->severity);
        }

        $q->orderByRaw("FIELD(severity, 'critical', 'warning')")->orderBy('due_date');

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
