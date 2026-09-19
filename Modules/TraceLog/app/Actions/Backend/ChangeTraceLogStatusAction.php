<?php

namespace Modules\TraceLog\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\TraceLog\Data\Requests\ChangeTraceLogStatusData;

class ChangeTraceLogStatusAction
{
    use AsAction;

    /** @return int số tem đã đổi trạng thái */
    public function handle(PrintLog $printLog, ChangeTraceLogStatusData $data, ?string $userId): int
    {
        return DB::transaction(function () use ($printLog, $data, $userId) {
            $logs = $data->apply_to_session && $printLog->print_session_id
                ? PrintLog::query()->where('print_session_id', $printLog->print_session_id)->get()
                : collect([$printLog]);

            foreach ($logs as $log) {
                // Chỉ các cột status* được phép đổi — xem PrintLog::booted().
                $log->update([
                    'status'            => $data->status,
                    'status_reason'     => $data->status === PrintLogStatus::Active ? null : $data->reason,
                    'status_changed_by' => $userId,
                    'status_changed_at' => now(),
                ]);
            }

            return $logs->count();
        });
    }
}
