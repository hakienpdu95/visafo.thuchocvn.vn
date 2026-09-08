<?php

namespace Modules\ActivityLog\Actions;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Models\AuditLog;

class RecordAuditLogAction
{
    use AsAction;

    public function handle(Model $model, string $action, array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'model_type' => $model::class,
            'model_id'   => $model->getKey(),
            'action'     => $action,
            'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
