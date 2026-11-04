<?php

namespace Modules\HanoiCheck\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HanoiCheck\Exceptions\HanoiCheckRequestException;

class HanoiCheckClient
{
    public function __construct(
        private readonly HanoiCheckTokenService $tokens,
        private readonly HanoiCheckSigner $signer,
    ) {}

    public static function enabled(): bool
    {
        return (bool) config('services.hanoicheck.enabled');
    }

    public static function fake(): bool
    {
        return (bool) config('services.hanoicheck.fake', true);
    }

    public static function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    public function post(string $path, string $body): array
    {
        return $this->send('POST', $path, $body);
    }

    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, '', $query);
    }

    private function send(string $method, string $path, string $body, array $query = []): array
    {
        $path = HanoiCheckSigner::normalizePath($path);

        if (self::fake()) {
            Log::channel('hanoicheck')->info("Fake {$method} {$path}", ['bytes' => strlen($body)]);

            return ['http_status' => null, 'body' => null, 'json' => null, 'nonce' => null];
        }

        $response = $this->call($method, $path, $body, $query, $nonce);
        if ($response->status() === 401 && in_array($response->json('error_code'), [null, 'INVALID_TOKEN', 'UNAUTHENTICATED'], true)) {
            $this->tokens->forgetAccessToken();
            $response = $this->call($method, $path, $body, $query, $nonce);
        }

        if (! $response->successful()) {
            throw HanoiCheckRequestException::fromStatus($response->status(), $response->json(), $response->body());
        }

        return ['http_status' => $response->status(), 'body' => $response->body(), 'json' => $response->json(), 'nonce' => $nonce];
    }

    private function call(string $method, string $path, string $body, array $query, ?string &$nonce): Response
    {
        $headers = ['Authorization' => 'Bearer ' . $this->tokens->token()];
        if (HanoiCheckSigner::requiresSignature($path)) {
            $signed = $this->signer->headers($method, $path, $body);
            $nonce = $signed['X-Nonce'];
            $headers += $signed;
        }

        $url = config('services.hanoicheck.base_url') . $path . ($query === [] ? '' : '?' . http_build_query($query));

        try {
            $request = Http::withHeaders($headers)
                ->acceptJson()
                ->timeout((int) config('services.hanoicheck.timeout', 30))
                ->withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]]);

            return $method === 'GET'
                ? $request->get($url)
                : $request->withBody($body, 'application/json')->send($method, $url);
        } catch (ConnectionException $e) {
            throw new HanoiCheckRequestException('CONNECTION_FAILED', $e->getMessage());
        }
    }
}
