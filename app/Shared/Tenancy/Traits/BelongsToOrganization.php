<?php

namespace App\Shared\Tenancy\Traits;

trait BelongsToOrganization
{
    public function scopeWithoutTenant($query)
    {
        return $query;
    }
}
