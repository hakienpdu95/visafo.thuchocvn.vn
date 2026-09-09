<?php

namespace App\Shared\Tenancy;

final class TenantContext
{
    public static function get(): mixed
    {
        return null;
    }

    public static function getOrganizationId(): ?string
    {
        return null;
    }

    public static function isSet(): bool
    {
        return false;
    }
}
