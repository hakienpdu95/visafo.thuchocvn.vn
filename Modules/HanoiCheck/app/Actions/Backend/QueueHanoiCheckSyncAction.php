<?php

namespace Modules\HanoiCheck\Actions\Backend;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Jobs\SyncToHanoiCheckJob;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\SalesOrder\Models\PrintLog;

class QueueHanoiCheckSyncAction
{
    use AsAction;

    private static array $queued = [];

    public function handle(Model $model): int
    {
        if (! HanoiCheckClient::enabled()) {
            return 0;
        }

        $count = 0;
        foreach ($this->targets($model) as [$entity, $id]) {
            $key = $entity->value . ':' . $id;
            if (isset(self::$queued[$key])) {
                continue;
            }
            self::$queued[$key] = true;

            SyncToHanoiCheckJob::dispatch($entity->value, (string) $id)
                ->delay(now()->addSeconds((int) config('services.hanoicheck.debounce_seconds', 15)));
            $count++;
        }

        return $count;
    }

    public static function resetQueued(): void
    {
        self::$queued = [];
    }

    private function targets(Model $model): array
    {
        return match (true) {
            $model instanceof PrintLog => $model->product_batch_id ? [[SyncEntity::ProductBatch, $model->product_batch_id]] : [],
            $model instanceof GoodsReceipt => $model->batches()->withTrashed()->pluck('id')->map(fn ($id) => [SyncEntity::ProductBatch, $id])->all(),
            default => ($entity = SyncEntity::fromModel($model)) ? [[$entity, $model->getKey()]] : [],
        };
    }
}
