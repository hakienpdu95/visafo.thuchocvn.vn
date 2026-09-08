<?php

namespace Modules\Sapo\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends TenantAwareModel
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'external_system',
        'external_system_id',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(ExternalOrder::class);
    }
}
