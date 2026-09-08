<?php

namespace Modules\ActivityLog\Observers;

use Illuminate\Database\Eloquent\Model;
use Modules\ActivityLog\Actions\RecordAuditLogAction;

class AuditableObserver
{
    private const IGNORED_KEYS = ['updated_at'];

    public function updated(Model $model): void
    {
        $changed = collect($model->getChanges())
            ->except(self::IGNORED_KEYS)
            ->all();

        if (empty($changed)) {
            return;
        }

        $original = collect($model->getOriginal())
            ->only(array_keys($changed))
            ->all();

        RecordAuditLogAction::run($model, 'update', $original, $changed);
    }

    public function deleted(Model $model): void
    {
        RecordAuditLogAction::run($model, 'delete', $model->getOriginal(), []);
    }
}
