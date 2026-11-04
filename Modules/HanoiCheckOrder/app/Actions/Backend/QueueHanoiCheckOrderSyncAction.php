<?php

namespace Modules\HanoiCheckOrder\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheck\Support\HanoiCheckDispatchGuard;
use Modules\HanoiCheckOrder\Enums\OrderSyncStatus;
use Modules\HanoiCheckOrder\Jobs\SyncHanoiCheckOrderJob;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;

class QueueHanoiCheckOrderSyncAction
{
    use AsAction;

    public function __construct(private readonly HanoiCheckDispatchGuard $guard) {}

    public function handle(string $salesOrderId, bool $immediate = false): bool
    {
        if (! HanoiCheckClient::enabled()) {
            return false;
        }

        $link = HanoiCheckOrderLink::query()->where('sales_order_id', $salesOrderId)->first();
        if ($link === null || $link->sync_status === OrderSyncStatus::Locked) {
            return false;
        }

        if (! $this->guard->once('order:' . $salesOrderId)) {
            return false;
        }
        $link->forceFill(['sync_status' => OrderSyncStatus::Queued])->saveQuietly();

        $job = SyncHanoiCheckOrderJob::dispatch($link->id);
        if (! $immediate) {
            $job->delay(now()->addSeconds((int) config('services.hanoicheck.debounce_seconds', 15)));
        }

        return true;
    }
}
