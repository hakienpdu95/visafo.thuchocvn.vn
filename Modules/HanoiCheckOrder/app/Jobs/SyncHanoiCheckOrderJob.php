<?php

namespace Modules\HanoiCheckOrder\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheckOrder\Actions\Backend\SyncHanoiCheckOrderAction;
use Modules\HanoiCheckOrder\Enums\OrderSyncStatus;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Throwable;

class SyncHanoiCheckOrderJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries = 12;

    public int $uniqueFor = 3600;

    public int $timeout = 180;

    public function __construct(public string $linkId)
    {
        $this->onQueue(config('services.hanoicheck.queue', 'hanoicheck'));
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return config('services.hanoicheck.backoff', [60, 300, 1800, 7200]);
    }

    public function uniqueId(): string
    {
        return 'order:' . $this->linkId;
    }

    public function handle(SyncHanoiCheckOrderAction $action): void
    {
        $link = HanoiCheckOrderLink::query()->find($this->linkId);
        if ($link === null || ! HanoiCheckClient::enabled()) {
            return;
        }

        if ($action->handle($link, $this->attempts()) === OrderSyncStatus::Waiting) {
            $this->release(max(15, (int) config('services.hanoicheck.settle_seconds', 5) * 3));
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::channel('hanoicheck')->error("Đồng bộ đơn HanoiCheck thất bại [link {$this->linkId}]", ['error' => $e?->getMessage()]);
    }
}
