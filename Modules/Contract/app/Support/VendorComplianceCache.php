<?php

namespace Modules\Contract\Support;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\Cache;

final class VendorComplianceCache
{
    public const TTL_SECONDS = 300;

    public static function key(string $suffix): string
    {
        return self::prefix() . ':v' . Cache::get(self::versionKey(), 1) . ':' . $suffix;
    }

    public static function bump(): void
    {
        Cache::forever(self::versionKey(), (int) Cache::get(self::versionKey(), 1) + 1);
    }

    private static function versionKey(): string
    {
        return self::prefix() . ':ver';
    }

    private static function prefix(): string
    {
        return 'vendor-compliance:' . (TenantContext::getOrganizationId() ?? 'default');
    }
}
