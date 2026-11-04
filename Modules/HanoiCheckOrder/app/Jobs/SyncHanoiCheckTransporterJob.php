<?php

namespace Modules\HanoiCheckOrder\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\HanoiCheck\Exceptions\HanoiCheckRequestException;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheckOrder\Actions\Backend\SyncHanoiCheckTransporterAction;
use Modules\HanoiCheckOrder\Models\HanoiCheckTransporter;

class SyncHanoiCheckTransporterJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public string $transporterId)
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
        return 'transporter:' . $this->transporterId;
    }

    public function handle(SyncHanoiCheckTransporterAction $action): void
    {
        $transporter = HanoiCheckTransporter::query()->find($this->transporterId);
        if ($transporter === null || ! HanoiCheckClient::enabled()) {
            return;
        }

        try {
            $action->handle($transporter, $this->attempts());
        } catch (HanoiCheckRequestException $e) {
            if (! $e->retryable) {
                $this->fail($e);

                return;
            }

            throw $e;
        }
    }
}
