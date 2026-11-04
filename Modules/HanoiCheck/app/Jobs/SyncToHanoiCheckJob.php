<?php

namespace Modules\HanoiCheck\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Enums\SyncStatus;
use Modules\HanoiCheck\Exceptions\HanoiCheckRequestException;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheck\Actions\Backend\SyncHanoiCheckRecordAction;
use Throwable;

class SyncToHanoiCheckJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries;

    public int $uniqueFor = 3600;

    public int $timeout = 120;

    public function __construct(public string $entity, public string $localId)
    {
        $this->onQueue(config('services.hanoicheck.queue', 'hanoicheck'));
        $this->afterCommit();
        $this->tries = count(config('services.hanoicheck.backoff', [])) + 1;
    }

    public function backoff(): array
    {
        return config('services.hanoicheck.backoff', [60, 300, 1800, 7200]);
    }

    public function uniqueId(): string
    {
        return $this->entity . ':' . $this->localId;
    }

    public function handle(SyncHanoiCheckRecordAction $action): void
    {
        if (! HanoiCheckClient::enabled()) {
            return;
        }

        try {
            $status = $action->handle(SyncEntity::from($this->entity), $this->localId, $this->attempts());
        } catch (HanoiCheckRequestException $e) {
            if (! $e->retryable) {
                $this->fail($e);

                return;
            }

            throw $e;
        }

        if ($status === SyncStatus::Pending) {
            $this->release(15);
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::channel('hanoicheck')->error("Đồng bộ HanoiCheck thất bại [{$this->entity}] ID [{$this->localId}]", [
            'error' => $e?->getMessage(),
        ]);
    }
}
