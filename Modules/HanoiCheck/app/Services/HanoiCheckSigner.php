<?php

namespace Modules\HanoiCheck\Services;

use Illuminate\Support\Str;
use Modules\HanoiCheck\Exceptions\HanoiCheckRequestException;

class HanoiCheckSigner
{
    public const UNSIGNED_PATHS = [
        '/api/supplier/token',
        '/api/supplier/refresh_token',
        '/api/supplier/standard-foods',
        '/api/supplier/student-groups',
        '/api/supplier/meal-types',
        '/api/supplier/measures',
    ];

    public static function requiresSignature(string $path): bool
    {
        return ! in_array(self::normalizePath($path), self::UNSIGNED_PATHS, true);
    }

    public static function normalizePath(string $path): string
    {
        return '/' . ltrim(strtok($path, '?') ?: $path, '/');
    }

    public function headers(string $method, string $path, string $body, ?int $timestamp = null, ?string $nonce = null): array
    {
        $secret = (string) config('services.hanoicheck.hmac_secret');
        if ($secret === '') {
            throw new HanoiCheckRequestException('config_missing', 'Chưa cấu hình HANOICHECK_HMAC_SECRET (khóa ký dữ liệu).', retryable: false);
        }

        $timestamp ??= time();
        $nonce ??= (string) Str::uuid();

        return [
            'X-Timestamp' => (string) $timestamp,
            'X-Nonce'     => $nonce,
            'X-Signature' => self::signature($method, $path, $body, $timestamp, $nonce, $secret),
        ];
    }

    public static function signature(string $method, string $path, string $body, int $timestamp, string $nonce, string $secret): string
    {
        $canonical = implode("\n", [
            strtoupper($method),
            self::normalizePath($path),
            (string) $timestamp,
            $nonce,
            hash('sha256', $body),
        ]);

        return base64_encode(hash_hmac('sha256', $canonical, $secret, true));
    }
}
