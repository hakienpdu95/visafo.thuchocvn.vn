<?php

namespace Modules\HanoiCheckOrder\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\HanoiCheck\Actions\Backend\CallHanoiCheckApiAction;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Enums\SyncStatus;
use Modules\HanoiCheck\Queries\GetHanoiCheckSyncStateHandler;
use Modules\HanoiCheck\Queries\GetHanoiCheckSyncStateQuery;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheckOrder\Models\HanoiCheckTransporter;

class SyncHanoiCheckTransporterAction
{
    use AsAction;

    public const PATH = '/api/supplier/users/merge';

    public function __construct(
        private readonly CallHanoiCheckApiAction $call,
        private readonly GetHanoiCheckSyncStateHandler $state,
    ) {}

    public function handle(HanoiCheckTransporter $transporter, int $attempt = 1): SyncStatus
    {
        $payload = [$transporter->toHanoiCheckRecord()];
        $hash = hash('sha256', HanoiCheckClient::encode($payload));
        $state = $this->state->handle(new GetHanoiCheckSyncStateQuery(SyncEntity::Transporter, $transporter->id, self::environment(), self::PATH));

        if ($state['synced'] && $state['last']->payload_hash === $hash) {
            return $state['last']->status;
        }

        return $this->call->handle(SyncEntity::Transporter, $transporter->id, self::PATH, $payload, $attempt)['status'];
    }

    public static function environment(): string
    {
        return HanoiCheckClient::fake() ? 'fake' : (string) config('services.hanoicheck.environment');
    }
}
