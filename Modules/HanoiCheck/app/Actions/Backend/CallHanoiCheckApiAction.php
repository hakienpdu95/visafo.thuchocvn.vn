<?php

namespace Modules\HanoiCheck\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Enums\SyncStatus;
use Modules\HanoiCheck\Exceptions\HanoiCheckRequestException;
use Modules\HanoiCheck\Models\HanoiCheckSyncLog;
use Modules\HanoiCheck\Services\HanoiCheckClient;
use Throwable;

class CallHanoiCheckApiAction
{
    use AsAction;

    public function __construct(private readonly HanoiCheckClient $client) {}

    public function handle(SyncEntity $entity, string $localId, string $path, array $payload, int $attempt = 1): array
    {
        $body = HanoiCheckClient::encode($payload);
        $hash = hash('sha256', $body);
        $environment = HanoiCheckClient::fake() ? 'fake' : (string) config('services.hanoicheck.environment');
        $started = hrtime(true);

        try {
            $result = $this->client->post($path, $body);
        } catch (Throwable $e) {
            $isApi = $e instanceof HanoiCheckRequestException;
            HanoiCheckSyncLog::query()->create([
                'entity_type'     => $entity,
                'local_id'        => $localId,
                'status'          => SyncStatus::Failed,
                'attempt'         => $attempt,
                'environment'     => $environment,
                'http_method'     => 'POST',
                'endpoint'        => $path,
                'request_payload' => $payload,
                'payload_hash'    => $hash,
                'http_status'     => $isApi ? $e->httpStatus : null,
                'response_body'   => $isApi ? $e->responseBody : null,
                'error_code'      => $isApi ? $e->errorCode : class_basename($e),
                'error_message'   => $e->getMessage(),
                'duration_ms'     => (int) ((hrtime(true) - $started) / 1_000_000),
            ]);

            throw $e;
        }

        $status = HanoiCheckClient::fake() ? SyncStatus::Fake : SyncStatus::Accepted;
        HanoiCheckSyncLog::query()->create([
            'entity_type'     => $entity,
            'local_id'        => $localId,
            'status'          => $status,
            'attempt'         => $attempt,
            'environment'     => $environment,
            'http_method'     => 'POST',
            'endpoint'        => $path,
            'request_payload' => $payload,
            'payload_hash'    => $hash,
            'http_status'     => $result['http_status'],
            'response_body'   => $result['body'],
            'request_nonce'   => $result['nonce'],
            'duration_ms'     => (int) ((hrtime(true) - $started) / 1_000_000),
        ]);

        return ['status' => $status, 'json' => $result['json'], 'hash' => $hash];
    }
}
