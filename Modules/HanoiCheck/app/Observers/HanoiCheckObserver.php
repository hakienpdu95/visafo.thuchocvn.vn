<?php

namespace Modules\HanoiCheck\Observers;

use Illuminate\Database\Eloquent\Model;
use Modules\HanoiCheck\Actions\Backend\QueueHanoiCheckSyncAction;

class HanoiCheckObserver
{
    private const IGNORED = ['updated_at', 'order_column', 'print_count', 'last_printed_at', 'last_print_session_id', 'current_qty'];

    public function saved(Model $model): void
    {
        if ($model->wasRecentlyCreated || array_diff(array_keys($model->getChanges()), self::IGNORED) !== []) {
            QueueHanoiCheckSyncAction::run($model);
        }
    }

    public function deleted(Model $model): void
    {
        QueueHanoiCheckSyncAction::run($model);
    }

    public function restored(Model $model): void
    {
        QueueHanoiCheckSyncAction::run($model);
    }
}
