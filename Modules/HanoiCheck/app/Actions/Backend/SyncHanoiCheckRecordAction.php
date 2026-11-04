<?php

namespace Modules\HanoiCheck\Actions\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Enums\SyncStatus;
use Modules\HanoiCheck\Exceptions\HanoiCheckRequestException;
use Modules\HanoiCheck\Models\HanoiCheckSyncLog;
use Modules\HanoiCheck\Queries\GetHanoiCheckSyncStateHandler;
use Modules\HanoiCheck\Queries\GetHanoiCheckSyncStateQuery;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Modules\HanoiCheck\Support\HanoiCheckPayloadBuilder;
use Modules\Product\Models\Product;
use Throwable;

class SyncHanoiCheckRecordAction
{
    use AsAction;

    public const MASTER_ID = 'default';

    public function __construct(
        private readonly HanoiCheckClient $client,
        private readonly HanoiCheckPayloadBuilder $builder,
        private readonly GetHanoiCheckSyncStateHandler $state,
    ) {}

    public function handle(SyncEntity $entity, string $localId, int $attempt = 1): SyncStatus
    {
        $model = $this->load($entity, $localId);
        if ($entity !== SyncEntity::Master && ($model === null || $this->trashed($model))) {
            return $this->skip($entity, $localId, $attempt, 'Bản ghi đã xóa ở hệ thống nguồn; HanoiCheck không có API xóa nên giữ nguyên dữ liệu đã gửi.');
        }

        $pending = $this->syncDependencies($entity, $model, $attempt);
        if ($pending !== null) {
            return $pending;
        }

        $payload = $this->builder->build($entity, $model);

        if ($payload['errors'] !== []) {
            $this->log($entity, $localId, SyncStatus::Invalid, $attempt, $payload['requests'][0]['path'] ?? null, $payload['requests'][0]['records'] ?? [],
                errorCode: 'LOCAL_VALIDATION', errorMessage: implode(' | ', $payload['errors']));

            return SyncStatus::Invalid;
        }

        $status = SyncStatus::Skipped;
        foreach ($payload['requests'] as $request) {
            $body = HanoiCheckClient::encode($request['records']);
            $hash = hash('sha256', $body);
            $state = $this->state->handle(new GetHanoiCheckSyncStateQuery($entity, $localId, $this->environment(), $request['path']));
            $last = $state['last'];
            if ($state['synced'] && $last->payload_hash === $hash) {
                $status = $status === SyncStatus::Skipped ? $last->status : $status;

                continue;
            }

            $started = hrtime(true);
            try {
                $result = $this->client->post($request['path'], $body);
            } catch (Throwable $e) {
                $isApi = $e instanceof HanoiCheckRequestException;
                $this->log($entity, $localId, SyncStatus::Failed, $attempt, $request['path'], $request['records'], $hash,
                    httpStatus: $isApi ? $e->httpStatus : null,
                    responseBody: $isApi ? $e->responseBody : null,
                    errorCode: $isApi ? $e->errorCode : class_basename($e),
                    errorMessage: $e->getMessage(),
                    durationMs: $this->elapsed($started));

                throw $e;
            }

            $status = HanoiCheckClient::fake() ? SyncStatus::Fake : SyncStatus::Accepted;
            if ($status === SyncStatus::Fake) {
                $label = class_basename($entity->modelClass() ?? 'Master');
                Log::channel('hanoicheck')->info("Fake sync [{$label}] ID [{$localId}] to HanoiCheck", ['path' => $request['path']]);
            }

            $this->log($entity, $localId, $status, $attempt, $request['path'], $request['records'], $hash,
                httpStatus: $result['http_status'], responseBody: $result['body'], nonce: $result['nonce'], durationMs: $this->elapsed($started));
        }

        return $status;
    }

    private function syncDependencies(SyncEntity $entity, ?Model $model, int $attempt): ?SyncStatus
    {
        $deps = match (true) {
            $model instanceof ProductBatch => array_filter([
                [SyncEntity::Master, self::MASTER_ID],
                $model->product_id ? [SyncEntity::Product, (string) $model->product_id] : null,
                $model->goodsReceipt?->vendor_id ? [SyncEntity::Vendor, (string) $model->goodsReceipt->vendor_id] : null,
            ]),
            $model instanceof Product => [[SyncEntity::Master, self::MASTER_ID]],
            default => [],
        };

        $settle = (int) config('services.hanoicheck.settle_seconds', 5);
        foreach ($deps as [$depEntity, $depId]) {
            $state = $this->state->handle(new GetHanoiCheckSyncStateQuery($depEntity, $depId, $this->environment()));
            if ($state['synced']) {
                if ($state['last']?->status === SyncStatus::Accepted && $state['last']->created_at->gt(now()->subSeconds($settle))) {
                    return SyncStatus::Pending;
                }

                continue;
            }

            $status = $this->handle($depEntity, $depId, $attempt);
            if ($status === SyncStatus::Accepted || $status === SyncStatus::Pending) {
                return SyncStatus::Pending;
            }
            if (! $status->synced()) {
                $this->log($entity, (string) $model->getKey(), SyncStatus::Invalid, $attempt, null, [],
                    errorCode: 'DEPENDENCY_NOT_SYNCED', errorMessage: "Chưa đồng bộ được {$depEntity->label()} ({$depId}) mà bản ghi này tham chiếu.");

                return SyncStatus::Invalid;
            }
        }

        return null;
    }

    private function skip(SyncEntity $entity, string $localId, int $attempt, string $reason): SyncStatus
    {
        $last = $this->state->handle(new GetHanoiCheckSyncStateQuery($entity, $localId, $this->environment()))['last'];
        if ($last?->status !== SyncStatus::Skipped) {
            $this->log($entity, $localId, SyncStatus::Skipped, $attempt, null, [], errorMessage: $reason);
        }

        return SyncStatus::Skipped;
    }

    private function load(SyncEntity $entity, string $localId): ?Model
    {
        $class = $entity->modelClass();
        if ($class === null) {
            return null;
        }

        $query = $class::query();
        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        return $query->find($localId);
    }

    private function trashed(Model $model): bool
    {
        return method_exists($model, 'trashed') && $model->trashed();
    }

    private function environment(): string
    {
        return HanoiCheckClient::fake() ? 'fake' : (string) config('services.hanoicheck.environment');
    }

    private function log(
        SyncEntity $entity, string $localId, SyncStatus $status, int $attempt, ?string $path, array $payload, ?string $hash = null,
        ?int $httpStatus = null, ?string $responseBody = null, ?string $errorCode = null, ?string $errorMessage = null,
        ?string $nonce = null, ?int $durationMs = null,
    ): void {
        HanoiCheckSyncLog::query()->create([
            'entity_type'     => $entity,
            'local_id'        => $localId,
            'status'          => $status,
            'attempt'         => $attempt,
            'environment'     => $this->environment(),
            'http_method'     => $path === null ? null : 'POST',
            'endpoint'        => $path,
            'request_payload' => $payload,
            'payload_hash'    => $hash,
            'http_status'     => $httpStatus,
            'response_body'   => $responseBody,
            'error_code'      => $errorCode,
            'request_nonce'   => $nonce,
            'error_message'   => $errorMessage,
            'duration_ms'     => $durationMs,
        ]);
    }

    private function elapsed(int $started): int
    {
        return (int) ((hrtime(true) - $started) / 1_000_000);
    }
}
