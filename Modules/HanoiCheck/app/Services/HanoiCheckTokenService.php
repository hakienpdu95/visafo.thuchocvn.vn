<?php

namespace Modules\HanoiCheck\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Modules\HanoiCheck\Exceptions\HanoiCheckRequestException;

class HanoiCheckTokenService
{
    private const ACCESS_KEY = 'hanoicheck:access_token';

    private const REFRESH_KEY = 'hanoicheck:refresh_token';

    public function token(): string
    {
        if (HanoiCheckClient::fake()) {
            return 'fake-token';
        }

        $cached = $this->read(self::ACCESS_KEY);
        if ($cached !== null) {
            return $cached;
        }

        return Cache::lock(self::ACCESS_KEY . ':lock', 30)->block(20, fn () => $this->read(self::ACCESS_KEY) ?? $this->issue());
    }

    public function forgetAccessToken(): void
    {
        Cache::forget(self::ACCESS_KEY);
    }

    public function forgetAll(): void
    {
        Cache::forget(self::ACCESS_KEY);
        Cache::forget(self::REFRESH_KEY);
    }

    private function issue(): string
    {
        $refresh = $this->read(self::REFRESH_KEY);
        if ($refresh !== null) {
            Cache::forget(self::REFRESH_KEY);
            $data = $this->request('/api/supplier/refresh_token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refresh,
            ], false);
            if ($data !== null) {
                return $this->store($data);
            }
        }

        return $this->store($this->request('/api/supplier/token', ['grant_type' => 'client_credentials'], true));
    }

    private function request(string $path, array $params, bool $throw): ?array
    {
        $base = (string) config('services.hanoicheck.base_url');
        $clientId = config('services.hanoicheck.client_id');
        $secret = config('services.hanoicheck.client_secret');
        if ($base === '' || blank($clientId) || blank($secret)) {
            throw new HanoiCheckRequestException('config_missing', 'Chưa cấu hình HANOICHECK_BASE_URL / HANOICHECK_CLIENT_ID / HANOICHECK_CLIENT_SECRET.', retryable: false);
        }

        try {
            $response = Http::acceptJson()->asJson()
                ->timeout((int) config('services.hanoicheck.timeout', 30))
                ->withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]])
                ->post($base . $path, $params + ['client_id' => $clientId, 'client_secret' => $secret]);
        } catch (ConnectionException $e) {
            throw new HanoiCheckRequestException('CONNECTION_FAILED', $e->getMessage());
        }

        if ($response->successful() && filled($response->json('access_token'))) {
            return $response->json();
        }

        if (! $throw) {
            return null;
        }

        throw HanoiCheckRequestException::fromStatus($response->status(), $response->json(), $response->body());
    }

    private function store(array $data): string
    {
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $ttl = max(60, $expiresIn - (int) config('services.hanoicheck.token_expiry_buffer', 300));
        Cache::put(self::ACCESS_KEY, Crypt::encryptString($data['access_token']), $ttl);

        if (filled($data['refresh_token'] ?? null)) {
            $refreshTtl = (int) config('services.hanoicheck.refresh_token_ttl', 2592000);
            Cache::put(self::REFRESH_KEY, Crypt::encryptString($data['refresh_token']), max(60, $refreshTtl - 3600));
        }

        return $data['access_token'];
    }

    private function read(string $key): ?string
    {
        $value = Cache::get($key);

        return $value === null ? null : Crypt::decryptString($value);
    }
}
